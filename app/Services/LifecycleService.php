<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Retention / lifecycle brain (moat layer 3).
 *
 *  - rebuild(): recompute one customer_lifecycle row per customer from PAID
 *    orders — purchase cadence (avg interval), predicted next reorder, WhatsApp
 *    opt-in (true once they bought via WhatsApp), totals.
 *  - replenishmentDue(): customers whose predicted reorder is near (predictive
 *    replenishment) and who are opted-in and out of cooldown.
 *  - upcomingOccasions() / occasionTargets(): KSA occasion calendar nudges.
 *
 * The longer it runs the better the cadence prediction gets — accumulated
 * consumption data no single-store rival can match.
 */
class LifecycleService
{
    /** Fallback reorder horizon for one-time buyers (dates ≈ a month of supply). */
    private const DEFAULT_REORDER_DAYS = 35;

    private function ref(string $phone): string
    {
        return substr(preg_replace('/\D/', '', $phone), -9);
    }

    /** Recompute every customer's lifecycle row from paid orders. Returns count. */
    public function rebuild(): int
    {
        $orders = DB::table('orders')
            ->where('isPaid', 1)
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->orderBy('created_at')
            ->get(['name', 'phone', 'email', 'totalPrice', 'source', 'created_at']);

        $groups = [];
        foreach ($orders as $o) {
            $ref = $this->ref((string) $o->phone);
            if (strlen($ref) < 9) continue;
            $groups[$ref][] = $o;
        }

        $n = 0;
        foreach ($groups as $ref => $os) {
            $dates = array_map(fn ($o) => Carbon::parse($o->created_at), $os);
            $first = $dates[0];
            $last = end($dates);
            $count = count($os);
            $spent = array_sum(array_map(fn ($o) => (float) $o->totalPrice, $os));
            $optIn = false;
            foreach ($os as $o) { if (($o->source ?? '') === 'whatsapp') { $optIn = true; break; } }

            // Average interval between consecutive orders.
            $avg = null;
            if ($count >= 2) {
                $diffs = [];
                for ($i = 1; $i < $count; $i++) $diffs[] = $dates[$i - 1]->diffInDays($dates[$i]);
                $diffs = array_filter($diffs, fn ($d) => $d > 0);
                if ($diffs) $avg = (int) round(array_sum($diffs) / count($diffs));
            }
            $horizon = $avg ?? self::DEFAULT_REORDER_DAYS;
            $predicted = (clone $last)->addDays($horizon);

            $latest = end($os);
            DB::table('customer_lifecycle')->updateOrInsert(
                ['customer_ref' => $ref],
                [
                    'name' => $latest->name, 'phone' => $latest->phone, 'email' => $latest->email,
                    'orders_count' => $count, 'first_order_at' => $first, 'last_order_at' => $last,
                    'total_spent' => $spent, 'avg_interval_days' => $avg, 'predicted_next_at' => $predicted,
                    'wa_opt_in' => $optIn, 'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ],
            );
            $n++;
        }
        return $n;
    }

    /** Opted-in customers whose predicted reorder falls within $windowDays and who are out of cooldown. */
    public function replenishmentDue(int $windowDays = 7, int $cooldownDays = 21)
    {
        return DB::table('customer_lifecycle')
            ->where('wa_opt_in', true)
            ->whereNotNull('predicted_next_at')
            ->where('predicted_next_at', '<=', now()->addDays($windowDays))
            ->where(function ($q) use ($cooldownDays) {
                $q->whereNull('last_nudge_at')->orWhere('last_nudge_at', '<=', now()->subDays($cooldownDays));
            })
            ->orderBy('predicted_next_at')
            ->get();
    }

    /** KSA occasion calendar. Dates are Gregorian approximations — refine per the Hijri calendar. */
    public function occasionCalendar(): array
    {
        return [
            ['key' => 'national_day', 'name' => 'اليوم الوطني', 'emoji' => '🇸🇦', 'date' => '2026-09-23'],
            ['key' => 'founding_day', 'name' => 'يوم التأسيس', 'emoji' => '🇸🇦', 'date' => '2027-02-22'],
            ['key' => 'ramadan',      'name' => 'رمضان',        'emoji' => '🌙', 'date' => '2027-02-08'],
            ['key' => 'eid_fitr',     'name' => 'عيد الفطر',     'emoji' => '🌙', 'date' => '2027-03-10'],
            ['key' => 'eid_adha',     'name' => 'عيد الأضحى',    'emoji' => '🕋', 'date' => '2027-05-17'],
        ];
    }

    /** Occasions starting within $withinDays (so we nudge with enough lead time to order). */
    public function upcomingOccasions(int $withinDays = 21): array
    {
        $out = [];
        foreach ($this->occasionCalendar() as $o) {
            $d = Carbon::parse($o['date']);
            $daysAway = now()->startOfDay()->diffInDays($d, false);
            if ($daysAway >= 0 && $daysAway <= $withinDays) { $o['days_away'] = $daysAway; $out[] = $o; }
        }
        return $out;
    }

    /** Opted-in customers eligible for an occasion nudge (out of cooldown). */
    public function occasionTargets(int $cooldownDays = 21)
    {
        return DB::table('customer_lifecycle')
            ->where('wa_opt_in', true)
            ->where(function ($q) use ($cooldownDays) {
                $q->whereNull('last_nudge_at')->orWhere('last_nudge_at', '<=', now()->subDays($cooldownDays));
            })
            ->orderByDesc('total_spent')
            ->get();
    }

    public function markNudged(string $ref, string $type): void
    {
        DB::table('customer_lifecycle')->where('customer_ref', $ref)
            ->update(['last_nudge_at' => now(), 'last_nudge_type' => $type]);
    }
}

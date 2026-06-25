<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Master frequency cap for lifecycle/marketing emails.
 *
 * Each retention flow already has its own per-flow dedup (the *_sent_at columns).
 * This adds a cross-flow ceiling so a single customer can't receive several
 * different nudges in quick succession (e.g. reorder + review + win-back within
 * days). Applied to the scheduled lifecycle flows (reorder, review, win-back).
 *
 * Abandoned-cart is deliberately EXEMPT — it is an immediate, high-intent recovery
 * of a checkout the customer just started, not a periodic nudge. Its send still
 * COUNTS toward the cap for the other flows.
 */
class RetentionGuard
{
    /** Columns that record a lifecycle email was sent to the customer. */
    private const SENT_COLUMNS = [
        'reorder_reminder_sent_at',
        'review_request_sent_at',
        'winback_sent_at',
        'abandoned_reminder_sent_at',
    ];

    /** Minimum days between any two lifecycle emails to the same customer. */
    public static function gapDays(): int
    {
        return (int) config('services.tamrat.retention_min_gap_days', 5);
    }

    /**
     * True when this customer (by email) has already received any lifecycle email
     * within the cap window — the caller should SKIP without marking its own
     * *_sent_at, so the order stays eligible once the window passes.
     */
    public static function recentlyContacted(?string $email, ?int $gapDays = null): bool
    {
        $email = trim((string) $email);
        if ($email === '') {
            return false; // no email to dedupe on — let the per-flow guards decide
        }
        $gapDays = $gapDays ?? self::gapDays();
        if ($gapDays <= 0) {
            return false; // cap disabled
        }
        $since = Carbon::now()->subDays($gapDays);

        return DB::table('orders')
            ->where('email', $email)
            ->where(function ($q) use ($since) {
                foreach (self::SENT_COLUMNS as $col) {
                    $q->orWhere($col, '>=', $since);
                }
            })
            ->exists();
    }
}

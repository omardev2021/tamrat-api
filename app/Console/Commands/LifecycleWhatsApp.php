<?php

namespace App\Console\Commands;

use App\Services\LifecycleService;
use App\Services\WhatsAppLifecycleService;
use Illuminate\Console\Command;

/**
 * Predictive-replenishment + occasion WhatsApp nudges (retention engine).
 *
 *   php artisan lifecycle:whatsapp                  # normal run (gated send)
 *   php artisan lifecycle:whatsapp --dry-run        # never sends, just shows
 *   php artisan lifecycle:whatsapp --window=400     # widen replenishment horizon (testing)
 *   php artisan lifecycle:whatsapp --to=+9665...    # send ONE sample to a test number
 *
 * Sending is gated (services.lifecycle.wa_enabled + approved template). Only
 * opted-in customers (bought via WhatsApp) are ever targeted.
 */
class LifecycleWhatsApp extends Command
{
    protected $signature = 'lifecycle:whatsapp
        {--dry-run : Compute + show, never send}
        {--type=all : all|replenishment|occasion}
        {--window=7 : Replenishment look-ahead in days}
        {--cooldown=21 : Min days between nudges per customer}
        {--limit=200 : Max nudges this run}
        {--to= : Send ONE sample nudge to this phone (pipe test)}';

    protected $description = 'Send predictive-replenishment + occasion WhatsApp nudges to opted-in customers';

    public function handle(): int
    {
        $svc = new LifecycleService();
        $wa = new WhatsAppLifecycleService();
        $dry = (bool) $this->option('dry-run');

        $built = $svc->rebuild();
        $this->info("Rebuilt {$built} customer lifecycle profiles.");

        // --to: pipe test to a single number, no targeting.
        if ($to = $this->option('to')) {
            $row = (object) ['name' => 'اختبار', 'phone' => $to, 'customer_ref' => substr(preg_replace('/\D/', '', $to), -9)];
            $occ = $svc->upcomingOccasions()[0] ?? null;
            $type = $occ ? 'occasion' : 'replenishment';
            $text = $wa->compose($type, $row, $occ);
            $this->line("→ [{$type}] {$to}\n{$text}");
            if (!$dry) { $res = $wa->send($to, $text); $this->line('   ' . json_encode($res, JSON_UNESCAPED_UNICODE)); }
            return self::SUCCESS;
        }

        $type = (string) $this->option('type');
        $cooldown = (int) $this->option('cooldown');
        $limit = (int) $this->option('limit');
        $targets = [];

        if (in_array($type, ['all', 'replenishment'], true)) {
            foreach ($svc->replenishmentDue((int) $this->option('window'), $cooldown) as $r) {
                $targets[] = ['type' => 'replenishment', 'row' => $r, 'occasion' => null];
            }
        }
        if (in_array($type, ['all', 'occasion'], true)) {
            $occ = $svc->upcomingOccasions();
            if ($occ) {
                foreach ($svc->occasionTargets($cooldown) as $r) {
                    $targets[] = ['type' => 'occasion', 'row' => $r, 'occasion' => $occ[0]];
                }
            } else {
                $this->line('No occasions within window.');
            }
        }

        // De-dup by customer (don't double-nudge in one run) + cap.
        $seen = [];
        $targets = array_values(array_filter($targets, function ($t) use (&$seen) {
            $ref = $t['row']->customer_ref;
            if (isset($seen[$ref])) return false; $seen[$ref] = true; return true;
        }));
        $targets = array_slice($targets, 0, $limit);

        $this->info(count($targets) . ' target(s)' . ($dry ? ' (dry-run)' : ''));
        $sent = 0;
        foreach ($targets as $t) {
            $row = $t['row'];
            $text = $wa->compose($t['type'], $row, $t['occasion']);
            $this->line("→ [{$t['type']}] {$row->name} ({$row->phone})");
            if ($dry) continue;
            $res = $wa->send((string) $row->phone, $text);
            if (!empty($res['sent'])) { $svc->markNudged($row->customer_ref, $t['type']); $sent++; }
        }
        $this->info($dry ? 'Dry-run complete (nothing sent).' : "Done. Sent {$sent}.");
        return self::SUCCESS;
    }
}

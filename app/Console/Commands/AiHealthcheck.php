<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\TelegramNotifier;

/**
 * Health monitor for the AI stack. Runs every 10 min (scheduler). Two live checks:
 *   1. Anthropic API key/reachability — a 1-token call.
 *   2. The FAQ bot end-to-end — a synthetic question through the PUBLIC url (nginx + app + tools).
 *
 * Alerts to Telegram on the ok->down transition, re-alerts hourly while still down, and sends a
 * "recovered" note when it comes back. State is kept in cache so we never spam.
 *
 * Note: this runs inside the app it watches, so a total PHP/server outage won't self-alert —
 * pair with an external uptime ping (e.g. UptimeRobot on https://api.tamratdates.com/up) for that.
 */
class AiHealthcheck extends Command
{
    protected $signature = 'ai:healthcheck {--test : send a test alert and exit}';
    protected $description = 'Ping the AI stack (Anthropic + FAQ bot) and alert on Telegram if anything is down';

    private const STATE_KEY   = 'ai_health_state';   // 'ok' | 'down'
    private const LASTALERT_KEY = 'ai_health_last_alert';
    private const REALERT_SECONDS = 3600;            // re-alert at most hourly while down
    private const FAQ_URL = 'https://api.tamratdates.com/api/faq-bot';

    public function handle(): int
    {
        if ($this->option('test')) {
            TelegramNotifier::send("🔔 <b>Tamrat AI monitor</b>\nTest alert — the alert channel is working.");
            $this->info('Test alert sent.');
            return self::SUCCESS;
        }

        $failures = [];

        // 1) Anthropic key + API
        try {
            $res = Http::withHeaders([
                'x-api-key'         => (string) config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(20)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1,
                'messages'   => [['role' => 'user', 'content' => 'ping']],
            ]);
            if (!$res->successful()) {
                $failures[] = "Anthropic API: HTTP {$res->status()} " . mb_substr($res->body(), 0, 120);
            }
        } catch (\Throwable $e) {
            $failures[] = 'Anthropic API: ' . mb_substr($e->getMessage(), 0, 120);
        }

        // 2) FAQ bot end-to-end through the public URL
        try {
            $res = Http::timeout(30)->post(self::FAQ_URL, [
                'messages' => [['role' => 'user', 'content' => 'وش أنواع التمر عندكم؟']],
            ]);
            $reply = trim((string) ($res->json('reply') ?? ''));
            if (!$res->successful()) {
                $failures[] = "FAQ bot: HTTP {$res->status()}";
            } elseif ($reply === '') {
                $failures[] = 'FAQ bot: empty reply';
            }
        } catch (\Throwable $e) {
            $failures[] = 'FAQ bot: ' . mb_substr($e->getMessage(), 0, 120);
        }

        $healthy   = empty($failures);
        $prevState = Cache::get(self::STATE_KEY, 'ok');

        if ($healthy) {
            if ($prevState === 'down') {
                TelegramNotifier::send("🟢 <b>Tamrat AI recovered</b>\nAll checks passing again.");
            }
            Cache::put(self::STATE_KEY, 'ok', now()->addDays(2));
            $this->info('AI stack healthy.');
            return self::SUCCESS;
        }

        // Down — decide whether to alert (transition, or hourly re-alert).
        $lastAlert = (int) Cache::get(self::LASTALERT_KEY, 0);
        $shouldAlert = $prevState === 'ok' || (time() - $lastAlert) >= self::REALERT_SECONDS;

        if ($shouldAlert) {
            $body = "🔴 <b>Tamrat AI is DOWN</b>\n" . collect($failures)->map(fn ($f) => "• {$f}")->implode("\n")
                . "\n\nChecked: Anthropic API + FAQ bot (" . self::FAQ_URL . ")";
            TelegramNotifier::send($body);
            Cache::put(self::LASTALERT_KEY, time(), now()->addDays(2));
        }
        Cache::put(self::STATE_KEY, 'down', now()->addDays(2));

        $this->error('AI stack DOWN: ' . implode(' | ', $failures));
        return self::FAILURE;
    }
}

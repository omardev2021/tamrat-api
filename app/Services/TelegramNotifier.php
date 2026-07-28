<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Founder alert channel. Sends to Mohammed's Telegram chat via the existing bot.
 * Best-effort — never throws into the caller (a monitor must not crash on a failed alert).
 */
class TelegramNotifier
{
    public static function send(string $text): bool
    {
        $token = (string) config('services.telegram.bot_token');
        $chat  = (string) config('services.telegram.chat_id');
        if (!$token || !$chat) {
            Log::warning('[Telegram] not configured (TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID)');
            return false;
        }
        try {
            $res = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'                  => $chat,
                'text'                     => $text,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            ]);
            if (!$res->successful()) {
                Log::warning('[Telegram] send failed', ['status' => $res->status(), 'body' => mb_substr($res->body(), 0, 200)]);
            }
            return $res->successful();
        } catch (\Throwable $e) {
            Log::error('[Telegram] exception: ' . $e->getMessage());
            return false;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Mail\WinBack;
use App\Services\RetentionGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendWinBack extends Command
{
    protected $signature = 'retention:win-back {--test= : send a sample win-back to this email}';

    protected $description = 'Email lapsed customers (~75 days since last order) to win them back';

    public function handle(): int
    {
        $code = config('services.tamrat.winback_code', '');

        if ($test = $this->option('test')) {
            Mail::to($test)->send(new WinBack('صديقنا', $code));
            $this->info("Test win-back sent to {$test}");
            return self::SUCCESS;
        }

        // Lapsed window: 75–120 days since order. Beyond 120 = treat as fully churned.
        $from = now()->subDays(120);
        $to = now()->subDays(75);

        $orders = Order::where('isPaid', 1)
            ->whereNotNull('email')->where('email', '!=', '')
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('winback_sent_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $seen = [];
        $sent = 0;

        foreach ($orders as $o) {
            $email = strtolower(trim($o->email));

            if (isset($seen[$email])) {
                $o->winback_sent_at = now();
                $o->save();
                continue;
            }

            // Master frequency cap: skip if this customer got another lifecycle
            // email recently. Do NOT mark sent → stays eligible once the gap passes.
            if (RetentionGuard::recentlyContacted($o->email)) {
                continue;
            }

            // still active? (any newer paid order) → skip + mark handled
            $newer = Order::where('isPaid', 1)
                ->where('email', $o->email)
                ->where('created_at', '>', $o->created_at)
                ->exists();
            if ($newer) {
                $o->winback_sent_at = now();
                $o->save();
                continue;
            }

            $seen[$email] = true;
            $firstName = trim(strtok((string) $o->name, ' ')) ?: 'صديقنا';

            try {
                Mail::to($o->email)->send(new WinBack($firstName, $code));
                $o->winback_sent_at = now();
                $o->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Win-back failed for order ' . $o->id . ': ' . $e->getMessage());
            }
        }

        $this->info("Win-back emails sent: {$sent}");
        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Mail\ReorderReminder;
use App\Services\RetentionGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendReorderReminders extends Command
{
    protected $signature = 'retention:reorder-reminders {--test= : send a sample reminder to this email}';

    protected $description = 'Email customers whose last order was ~28 days ago to reorder (consumable retention)';

    public function handle(): int
    {
        // Test mode — verify rendering/sending without touching real customers
        if ($test = $this->option('test')) {
            Mail::to($test)->send(new ReorderReminder('صديقنا', 'تمر سكري كيلو'));
            $this->info("Test reorder reminder sent to {$test}");
            return self::SUCCESS;
        }

        // Reorder window: 28–45 days since the order. Older than that = win-back territory.
        $from = now()->subDays(45);
        $to = now()->subDays(28);

        $orders = Order::where('isPaid', 1)
            ->whereNotNull('email')->where('email', '!=', '')
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('reorder_reminder_sent_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $seen = [];
        $sent = 0;

        foreach ($orders as $o) {
            $email = strtolower(trim($o->email));

            // one reminder per customer per run
            if (isset($seen[$email])) {
                $o->reorder_reminder_sent_at = now();
                $o->save();
                continue;
            }

            // Master frequency cap: skip if this customer got another lifecycle
            // email recently. Do NOT mark sent → stays eligible once the gap passes.
            if (RetentionGuard::recentlyContacted($o->email)) {
                continue;
            }

            // already reordered? (a newer paid order exists) → skip + mark handled
            $newer = Order::where('isPaid', 1)
                ->where('email', $o->email)
                ->where('created_at', '>', $o->created_at)
                ->exists();
            if ($newer) {
                $o->reorder_reminder_sent_at = now();
                $o->save();
                continue;
            }

            $seen[$email] = true;
            $firstName = trim(strtok((string) $o->name, ' ')) ?: 'صديقنا';
            $item = OrderItem::where('order_id', $o->id)->with('product')->first();
            $lastProduct = $item && $item->product ? ($item->product->name_ar ?: $item->product->name_en) : '';

            try {
                Mail::to($o->email)->send(new ReorderReminder($firstName, $lastProduct));
                $o->reorder_reminder_sent_at = now();
                $o->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Reorder reminder failed for order ' . $o->id . ': ' . $e->getMessage());
            }
        }

        $this->info("Reorder reminders sent: {$sent}");
        return self::SUCCESS;
    }
}

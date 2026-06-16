<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Mail\AbandonedCart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendAbandonedCart extends Command
{
    protected $signature = 'retention:abandoned-cart {--test= : send a sample abandoned-cart email to this email}';

    protected $description = 'Email customers who started checkout (unpaid order) but did not pay within ~1–24h';

    public function handle(): int
    {
        if ($test = $this->option('test')) {
            $items = [
                ['name' => 'تمر سكري كيلو', 'qty' => 1, 'price' => 80],
                ['name' => 'تمر عجوة نصف كيلو', 'qty' => 2, 'price' => 40],
            ];
            Mail::to($test)->send(new AbandonedCart('صديقنا', $items, 'https://tamratdates.com/cart'));
            $this->info("Test abandoned-cart sent to {$test}");
            return self::SUCCESS;
        }

        // Unpaid orders created 1–24h ago = abandoned checkouts (order is created pre-payment)
        $from = now()->subHours(24);
        $to = now()->subHours(1);

        $orders = Order::where('isPaid', 0)
            ->whereNotNull('email')->where('email', '!=', '')
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('abandoned_reminder_sent_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $seen = [];
        $sent = 0;

        foreach ($orders as $o) {
            $email = strtolower(trim($o->email));

            if (isset($seen[$email])) {
                $o->abandoned_reminder_sent_at = now();
                $o->save();
                continue;
            }

            // already completed a purchase (this order or a later one)? → skip + mark handled
            $completed = Order::where('email', $o->email)
                ->where('isPaid', 1)
                ->where('created_at', '>=', $o->created_at)
                ->exists();
            if ($completed) {
                $o->abandoned_reminder_sent_at = now();
                $o->save();
                continue;
            }

            $seen[$email] = true;
            $firstName = trim(strtok((string) $o->name, ' ')) ?: 'صديقنا';

            $items = OrderItem::where('order_id', $o->id)->with('product')->get()->map(function ($it) {
                $p = $it->product;
                return [
                    'name' => $p ? ($p->name_ar ?: $p->name_en) : 'منتج',
                    'qty' => (int) $it->qty,
                    'price' => (float) $it->price,
                ];
            })->all();

            $resumeUrl = 'https://tamratdates.com/pay/' . $o->id;

            try {
                Mail::to($o->email)->send(new AbandonedCart($firstName, $items, $resumeUrl));
                $o->abandoned_reminder_sent_at = now();
                $o->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Abandoned-cart email failed for order ' . $o->id . ': ' . $e->getMessage());
            }
        }

        $this->info("Abandoned-cart emails sent: {$sent}");
        return self::SUCCESS;
    }
}

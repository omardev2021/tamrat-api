<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Mail\ReviewRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendReviewRequests extends Command
{
    protected $signature = 'retention:review-requests {--test= : send a sample review request to this email}';

    protected $description = 'Email customers ~6 days after their order to ask for a review (fuels off-site reviews / AEO)';

    public function handle(): int
    {
        $reviewUrl = config('services.tamrat.review_url');

        if ($test = $this->option('test')) {
            Mail::to($test)->send(new ReviewRequest('صديقنا', $reviewUrl));
            $this->info("Test review request sent to {$test}");
            return self::SUCCESS;
        }

        // ~6–9 days post-order: enough time for delivery (2–5 day shipping)
        $from = now()->subDays(9);
        $to = now()->subDays(6);

        $orders = Order::where('isPaid', 1)
            ->whereNotNull('email')->where('email', '!=', '')
            ->whereBetween('created_at', [$from, $to])
            ->whereNull('review_request_sent_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $seen = [];
        $sent = 0;

        foreach ($orders as $o) {
            $email = strtolower(trim($o->email));

            if (isset($seen[$email])) {
                $o->review_request_sent_at = now();
                $o->save();
                continue;
            }

            // cooldown: don't ask the same customer for a review more than once per 25 days
            $recentlyAsked = Order::where('email', $o->email)
                ->whereNotNull('review_request_sent_at')
                ->where('review_request_sent_at', '>', now()->subDays(25))
                ->exists();
            if ($recentlyAsked) {
                $o->review_request_sent_at = now();
                $o->save();
                continue;
            }

            $seen[$email] = true;
            $firstName = trim(strtok((string) $o->name, ' ')) ?: 'صديقنا';

            try {
                Mail::to($o->email)->send(new ReviewRequest($firstName, $reviewUrl));
                $o->review_request_sent_at = now();
                $o->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Review request failed for order ' . $o->id . ': ' . $e->getMessage());
            }
        }

        $this->info("Review requests sent: {$sent}");
        return self::SUCCESS;
    }
}

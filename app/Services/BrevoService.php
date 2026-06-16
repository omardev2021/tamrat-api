<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoService
{
    /**
     * Upsert a customer into Brevo as a marketing contact with the attributes
     * the retention automations fire from (reorder reminder, win-back, review).
     * Best-effort: failures are logged and never break payment confirmation.
     */
    public function upsertCustomer(Order $order): void
    {
        try {
            $key = config('services.brevo.key');
            $listId = (int) config('services.brevo.list_id');
            if (!$key || empty($order->email)) {
                return;
            }

            // Aggregate this customer's history (by email)
            $orders = Order::where('email', $order->email)->where('isPaid', 1)->get();
            $ordersCount = $orders->count();
            $totalSpent = round((float) $orders->sum('totalPrice'), 2);
            $firstName = trim(strtok((string) $order->name, ' '));

            $lastItem = OrderItem::where('order_id', $order->id)->with('product')->first();
            $lastProduct = $lastItem && $lastItem->product
                ? ($lastItem->product->name_ar ?: $lastItem->product->name_en)
                : '';

            $attributes = [
                'FIRSTNAME' => $firstName,
                'LAST_ORDER_DATE' => now()->format('Y-m-d'),
                'ORDERS_COUNT' => $ordersCount,
                'TOTAL_SPENT' => $totalSpent,
                'LAST_PRODUCT' => $lastProduct,
            ];
            // Phone/SMS deliberately omitted here — added in the WhatsApp/SMS phase
            // once numbers are normalised to E.164 (avoids contact-create failures).

            Http::withHeaders([
                'api-key' => $key,
                'content-type' => 'application/json',
                'accept' => 'application/json',
            ])->timeout(8)->post('https://api.brevo.com/v3/contacts', [
                'email' => $order->email,
                'attributes' => $attributes,
                'listIds' => $listId ? [$listId] : [],
                'updateEnabled' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Brevo contact upsert failed for order ' . $order->id . ': ' . $e->getMessage());
        }
    }
}

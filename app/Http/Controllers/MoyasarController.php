<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MoyasarController extends Controller
{
    /**
     * Verify a Moyasar payment server-side before marking the order paid.
     * The payment id alone is not proof of payment — we fetch the payment
     * from Moyasar with the secret key and check status, amount, currency
     * and that it belongs to this order.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|string',
            'order' => 'required',
        ]);

        $order = Order::find($request->order);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Idempotent: re-verifying the same paid order succeeds quietly
        if ($order->isPaid && $order->payment_id === $request->payment_id) {
            return response()->json(['message' => 'paid successfully', 'status' => 'paid']);
        }

        $secret = config('services.moyasar.secret');
        if (!$secret) {
            return response()->json(['message' => 'Payment verification is not configured'], 500);
        }

        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->get('https://api.moyasar.com/v1/payments/' . urlencode($request->payment_id));

        if (!$response->ok()) {
            return response()->json(['message' => 'Could not fetch payment from gateway'], 502);
        }

        $payment = $response->json();

        if (($payment['status'] ?? null) !== 'paid') {
            return response()->json([
                'message' => $payment['source']['message'] ?? 'Payment was not completed',
                'status' => $payment['status'] ?? 'unknown',
            ], 422);
        }

        $expectedAmount = (int) round(((float) $order->totalPrice) * 100);
        if ((int) ($payment['amount'] ?? 0) !== $expectedAmount || ($payment['currency'] ?? '') !== 'SAR') {
            return response()->json(['message' => 'Payment amount mismatch', 'status' => 'mismatch'], 422);
        }

        $metaOrder = $payment['metadata']['order_id'] ?? null;
        if ($metaOrder !== null && (string) $metaOrder !== (string) $order->id) {
            return response()->json(['message' => 'Payment does not belong to this order', 'status' => 'mismatch'], 422);
        }

        $order->isPaid = 1;
        $order->payment_id = $request->payment_id;
        $order->save();

        // Fire the GA4 purchase server-side (Measurement Protocol) so revenue is
        // captured for every paid order regardless of ad blockers / client issues.
        $this->sendGa4Purchase($order, $request);

        return response()->json(['message' => 'paid successfully', 'status' => 'paid']);
    }

    /**
     * Send the GA4 `purchase` event via the Measurement Protocol.
     * Best-effort: any failure is logged and never breaks payment confirmation.
     */
    private function sendGa4Purchase(Order $order, Request $request): void
    {
        try {
            $measurementId = config('services.ga4.measurement_id');
            $apiSecret = config('services.ga4.mp_secret');
            if (!$measurementId || !$apiSecret) {
                return;
            }

            // client_id + session_id come from the browser (the GA cookies) so the
            // event joins the user's existing session and keeps source attribution.
            $clientId = $request->input('ga_client_id');
            if (!$clientId) {
                // No GA cookie (e.g. ad blocker) — still record revenue, attribution lost.
                $clientId = random_int(1000000000, 2147483647) . '.' . time();
            }
            $sessionId = $request->input('ga_session_id');

            $items = OrderItem::where('order_id', $order->id)->with('product')->get()
                ->map(function ($it) {
                    $p = $it->product;
                    return [
                        'item_id' => (string) $it->product_id,
                        'item_name' => $p ? ($p->name_en ?? $p->name_ar ?? 'Product') : 'Product',
                        'price' => (float) $it->price,
                        'quantity' => (int) $it->qty,
                    ];
                })->values()->all();

            $params = [
                'transaction_id' => (string) $order->id,
                'currency' => 'SAR',
                'value' => (float) $order->totalPrice,
                'shipping' => (float) ($order->shippingPrice ?? 0),
                'tax' => (float) ($order->taxPrice ?? 0),
                'items' => $items,
                'engagement_time_msec' => 100,
            ];
            if ($sessionId) {
                $params['session_id'] = (string) $sessionId;
            }

            $payload = [
                'client_id' => (string) $clientId,
                'events' => [[
                    'name' => 'purchase',
                    'params' => $params,
                ]],
            ];

            Http::timeout(5)->post(
                'https://www.google-analytics.com/mp/collect?measurement_id='
                    . urlencode($measurementId) . '&api_secret=' . urlencode($apiSecret),
                $payload
            );
        } catch (\Throwable $e) {
            Log::warning('GA4 MP purchase failed for order ' . $order->id . ': ' . $e->getMessage());
        }
    }
}

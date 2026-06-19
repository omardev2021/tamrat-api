<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        $this->markOrderPaid($order, $request->payment_id, $request);

        return response()->json(['message' => 'paid successfully', 'status' => 'paid']);
    }

    /**
     * Moyasar server-to-server webhook (payment_paid). The authoritative confirmation
     * for WhatsApp orders — fires even if the customer never returns to the success page.
     * Configure the URL + a shared secret_token in the Moyasar dashboard.
     */
    public function webhook(Request $request)
    {
        $expected = config('services.moyasar.webhook_secret');
        if ($expected && !hash_equals((string) $expected, (string) $request->input('secret_token'))) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $payment   = (array) $request->input('data', []);
        $paymentId = $payment['id'] ?? null;
        $orderId   = $payment['metadata']['order_id'] ?? null;
        if (!$paymentId || !$orderId) return response()->json(['message' => 'ignored'], 200);

        $order = Order::find($orderId);
        if (!$order) return response()->json(['message' => 'order not found'], 200);

        // Never trust the webhook body alone — re-fetch the payment with the secret key.
        $secret = config('services.moyasar.secret');
        if (!$secret) return response()->json(['message' => 'not configured'], 200);
        $resp = Http::withBasicAuth($secret, '')->acceptJson()
            ->get('https://api.moyasar.com/v1/payments/' . urlencode($paymentId));
        if (!$resp->ok()) return response()->json(['message' => 'fetch failed'], 200);
        $p = $resp->json();

        if (($p['status'] ?? null) !== 'paid') return response()->json(['message' => 'not paid'], 200);
        $expectedAmount = (int) round(((float) $order->totalPrice) * 100);
        if ((int) ($p['amount'] ?? 0) !== $expectedAmount || ($p['currency'] ?? '') !== 'SAR') {
            return response()->json(['message' => 'amount mismatch'], 200);
        }

        $this->markOrderPaid($order, (string) $paymentId, $request);
        return response()->json(['message' => 'ok'], 200);
    }

    /**
     * Mark an order paid + fire side-effects EXACTLY ONCE (atomic guard against the
     * success-page verify and the webhook racing). Side-effects: GA4 purchase, Brevo,
     * confirmation email, and — for WhatsApp orders — a "paid ✅" push into the chat.
     */
    private function markOrderPaid(Order $order, string $paymentId, ?Request $request = null): void
    {
        // Already settled with this payment → nothing to do.
        if ((int) $order->isPaid === 1 && $order->payment_id === $paymentId) return;

        // Atomic: only the first caller flips 0→1 and runs side-effects.
        $won = Order::where('id', $order->id)->where('isPaid', 0)
            ->update(['isPaid' => 1, 'payment_id' => $paymentId]);
        if (!$won) return;

        $order->refresh();
        $this->sendGa4Purchase($order, $request ?? request());
        $this->sendOrderConfirmationEmail($order);
        try { (new \App\Services\BrevoService())->upsertCustomer($order); } catch (\Throwable $e) {
            Log::warning('Brevo upsert failed for order ' . $order->id . ': ' . $e->getMessage());
        }

        \App\Models\CommerceEvent::record([
            'type' => 'paid', 'converted' => true, 'order_id' => $order->id,
            'conversation_id' => $order->conversation_id ? (int) $order->conversation_id : null,
            'customer_ref' => $order->phone ? substr(preg_replace('/\D/', '', (string) $order->phone), -9) : null,
            'price_point' => (float) $order->totalPrice, 'meta' => ['source' => $order->source],
        ]);

        if ($order->source === 'whatsapp' && $order->conversation_id) {
            $this->pushWhatsAppConfirmation($order);
        }
    }

    /** Post a "paid ✅" message into the Chatwoot thread that created this order. */
    private function pushWhatsAppConfirmation(Order $order): void
    {
        try {
            $base  = rtrim((string) config('services.chatwoot.base_url'), '/');
            $token = (string) config('services.chatwoot.api_token');
            $acct  = config('services.chatwoot.account_id');
            if (!$base || !$token) return;

            $total = rtrim(rtrim(number_format((float) $order->totalPrice, 2, '.', ''), '0'), '.');
            $name  = trim((string) $order->name);
            $msg = "تم استلام دفعتك ✅\n"
                . "طلبك رقم #{$order->id} بإجمالي {$total} ر.س." . ($name ? " شكراً {$name}!" : ' شكراً لك!') . " 🌴\n"
                . "بيوصلك خلال ٢–٥ أيام، وبنحدّثك أول ما يُشحن.";

            Http::withHeaders(['api_access_token' => $token])->acceptJson()->timeout(15)
                ->post("{$base}/api/v1/accounts/{$acct}/conversations/{$order->conversation_id}/messages", [
                    'content' => $msg, 'message_type' => 'outgoing', 'private' => false,
                ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp paid-confirmation push failed for order ' . $order->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Email the customer their order confirmation (and BCC the store owner).
     * Best-effort: failures are logged and never break payment confirmation.
     */
    private function sendOrderConfirmationEmail(Order $order): void
    {
        try {
            $items = OrderItem::where('order_id', $order->id)->with('product')->get();
            $notify = config('mail.order_notify');
            $mailable = new \App\Mail\OrderConfirmation($order, $items);

            if (!empty($order->email)) {
                $m = Mail::to($order->email);
                if ($notify) {
                    $m->bcc($notify);
                }
                $m->send($mailable);
            } elseif ($notify) {
                // Guest order with no email — at least notify the store owner.
                Mail::to($notify)->send($mailable);
            }
        } catch (\Throwable $e) {
            Log::warning('Order confirmation email failed for order ' . $order->id . ': ' . $e->getMessage());
        }
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

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

/**
 * Fulfillment handoff — the operator's view over paid orders that still need to
 * ship, plus the actions to record a shipment and mark delivery.
 *
 * Manual-first by design: the operator hands the box to a courier and records the
 * carrier + tracking number here. A carrier API (SMSA/Aramex) can later call
 * markShipped() with an auto-generated AWB — the rest of the flow is identical.
 *
 * Every endpoint is gated by a shared secret (X-Admin-Secret) and FAILS CLOSED:
 * if no secret is configured the endpoints deny all requests, so order PII is
 * never exposed by accident.
 */
class FulfillmentController extends Controller
{
    /** Statuses that still need operator action. */
    private const OPEN_STATUSES = ['pending', 'processing'];

    /**
     * GET /fulfillment/queue?status=pending
     * Lists paid orders for fulfillment (default: those still needing action),
     * oldest first, each with its line items for packing.
     */
    public function queue(Request $request)
    {
        if ($deny = $this->guard($request)) return $deny;

        $status = $request->query('status');
        $query = Order::where('isPaid', 1);

        if ($status) {
            $query->where('fulfillment_status', $status);
        } else {
            // Default view = anything not yet shipped/delivered/cancelled. Treat a
            // null fulfillment_status as 'pending' (covers any pre-existing paid order).
            $query->where(function ($q) {
                $q->whereIn('fulfillment_status', self::OPEN_STATUSES)
                  ->orWhereNull('fulfillment_status');
            });
        }

        $orders = $query->orderBy('created_at', 'asc')->get();
        $orderIds = $orders->pluck('id');
        $itemsByOrder = OrderItem::whereIn('order_id', $orderIds)->with('product')->get()
            ->groupBy('order_id');

        $payload = $orders->map(function ($o) use ($itemsByOrder) {
            return [
                'id'                 => $o->id,
                'created_at'         => $o->created_at,
                'name'               => $o->name,
                'phone'              => $o->phone,
                'email'              => $o->email,
                'country'            => $o->country,
                'city'               => $o->city,
                'address'            => $o->address,
                'weight'             => $o->weight,
                'totalPrice'         => $o->totalPrice,
                'source'             => $o->source,
                'fulfillment_status' => $o->fulfillment_status ?: 'pending',
                'carrier'            => $o->carrier,
                'awb'                => $o->awb,
                'shipped_at'         => $o->shipped_at,
                'items'              => ($itemsByOrder[$o->id] ?? collect())->map(function ($it) {
                    $p = $it->product;
                    return [
                        'product_id' => $it->product_id,
                        'name'       => $p ? ($p->name_ar ?: $p->name_en) : null,
                        'qty'        => $it->qty,
                        'weight'     => $it->weight,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'count'  => $payload->count(),
            'orders' => $payload,
        ]);
    }

    /**
     * POST /fulfillment/ship  { order_id, carrier, awb }
     * Records the shipment and notifies the customer exactly once.
     * Guardrails: order must exist and be paid; idempotent (re-calling updates the
     * tracking details but never re-notifies).
     */
    public function markShipped(Request $request)
    {
        if ($deny = $this->guard($request)) return $deny;

        $data = $request->validate([
            'order_id' => 'required|integer',
            'carrier'  => 'required|string|max:80',
            'awb'      => 'required|string|max:120',
        ]);

        $order = Order::find($data['order_id']);
        if (!$order) {
            return response()->json(['message' => 'order not found'], 404);
        }
        if ((int) $order->isPaid !== 1) {
            return response()->json(['message' => 'order is not paid — cannot ship'], 422);
        }

        $order->carrier = $data['carrier'];
        $order->awb = $data['awb'];
        $order->fulfillment_status = 'shipped';
        if (!$order->shipped_at) {
            $order->shipped_at = now();
        }
        $order->save();

        // Notify exactly once (the guardrail), best-effort.
        if (!$order->fulfillment_notified_at) {
            $this->sendShippedEmail($order);
            if ($order->source === 'whatsapp' && $order->conversation_id) {
                $this->pushWhatsAppShipped($order);
            }
            $order->fulfillment_notified_at = now();
            $order->save();
        }

        return response()->json([
            'message'      => 'order marked shipped',
            'order_id'     => $order->id,
            'carrier'      => $order->carrier,
            'awb'          => $order->awb,
            'tracking_url' => $this->trackingUrl($order->carrier, $order->awb),
            'notified'     => (bool) $order->fulfillment_notified_at,
        ]);
    }

    /**
     * POST /fulfillment/delivered  { order_id }
     * Marks an order delivered (closes the loop; also flips the legacy isDelivered).
     */
    public function markDelivered(Request $request)
    {
        if ($deny = $this->guard($request)) return $deny;

        $data = $request->validate(['order_id' => 'required|integer']);
        $order = Order::find($data['order_id']);
        if (!$order) {
            return response()->json(['message' => 'order not found'], 404);
        }

        $order->fulfillment_status = 'delivered';
        $order->isDelivered = 1;
        if (!$order->delivered_at) {
            $order->delivered_at = now();
        }
        $order->save();

        return response()->json(['message' => 'order marked delivered', 'order_id' => $order->id]);
    }

    /**
     * Access gate. Authorised when EITHER the caller is an authenticated admin
     * (Sanctum user with type 13 — used by the admin UI's logged-in token) OR a
     * valid shared secret is presented (X-Admin-Secret — for CLI/server-to-server
     * and a future carrier API). Returns a 401 response on failure, null on pass.
     */
    private function guard(Request $request)
    {
        $user = auth('sanctum')->user();
        if ($user && (int) $user->type === 13) {
            return null;
        }
        $secret = config('services.admin.secret');
        if ($secret && hash_equals((string) $secret, (string) $request->header('X-Admin-Secret'))) {
            return null;
        }
        return response()->json(['message' => 'unauthorized'], 401);
    }

    /** Build a customer-facing tracking URL for known carriers, or null. */
    private function trackingUrl(?string $carrier, ?string $awb): ?string
    {
        if (!$carrier || !$awb) return null;
        $map = (array) config('services.carriers', []);
        $key = strtolower(trim($carrier));
        // Match on a normalised key (e.g. "SMSA Express" → "smsa").
        foreach ($map as $name => $template) {
            if (str_contains($key, strtolower($name))) {
                return str_replace('{awb}', urlencode($awb), $template);
            }
        }
        return null;
    }

    /** Email the customer that their order shipped (best-effort; never throws). */
    private function sendShippedEmail(Order $order): void
    {
        try {
            if (empty($order->email)) return;
            $items = OrderItem::where('order_id', $order->id)->with('product')->get();
            $trackingUrl = $this->trackingUrl($order->carrier, $order->awb);
            Mail::to($order->email)->send(new \App\Mail\OrderShipped($order, $items, $trackingUrl));
        } catch (\Throwable $e) {
            Log::warning('Shipped email failed for order ' . $order->id . ': ' . $e->getMessage());
        }
    }

    /** Post a "shipped 🚚" message into the Chatwoot thread that created this order. */
    private function pushWhatsAppShipped(Order $order): void
    {
        try {
            $base  = rtrim((string) config('services.chatwoot.base_url'), '/');
            $token = (string) config('services.chatwoot.api_token');
            $acct  = config('services.chatwoot.account_id');
            if (!$base || !$token) return;

            $trackingUrl = $this->trackingUrl($order->carrier, $order->awb);
            $msg = "طلبك في الطريق 🚚\n"
                . "طلب رقم #{$order->id} تم شحنه عبر {$order->carrier}.\n"
                . "رقم التتبّع: {$order->awb}"
                . ($trackingUrl ? "\nتتبّع شحنتك: {$trackingUrl}" : '')
                . "\nبيوصلك خلال ٢–٥ أيام إن شاء الله 🌴";

            Http::withHeaders(['api_access_token' => $token])->acceptJson()->timeout(15)
                ->post("{$base}/api/v1/accounts/{$acct}/conversations/{$order->conversation_id}/messages", [
                    'content' => $msg, 'message_type' => 'outgoing', 'private' => false,
                ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp shipped push failed for order ' . $order->id . ': ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

/**
 * Commerce engine for the WhatsApp buying agent (Watar Commerce Agent — Tamrat driver).
 *
 * Money-safety: the agent proposes products + quantities; THIS service recomputes
 * every price, the subtotal, shipping, VAT, and total from the database. The agent
 * never sets a price. Pay link = the existing Moyasar hosted form at /pay/{id}.
 */
class CommerceService
{
    private const SHIPPING_FEE   = 25.0;   // SAR, KSA flat
    private const FREE_THRESHOLD = 250.0;  // SAR subtotal → free shipping
    private const ETA_DAYS       = '2–5';

    private const CATEGORY = [
        'ajwa' => 1, 'sukari' => 2, 'sagie' => 3, 'mabroom' => 4, 'majhool' => 5,
    ];

    private function storeUrl(): string
    {
        return rtrim((string) config('services.tamrat.store_url', 'https://tamratdates.com'), '/');
    }

    /** Search the live catalog. All filters optional. Returns compact rows for the agent. */
    public function searchProducts(array $f = []): array
    {
        $q = DB::table('products')
            ->leftJoin('products_meta', 'products_meta.product_id', '=', 'products.id')
            ->where('products.price', '>', 0);

        if (($f['in_stock'] ?? true)) $q->where('products.countInStock', '>', 0);

        if (!empty($f['query'])) {
            $term = trim((string) $f['query']);
            $q->where(function ($w) use ($term) {
                $w->where('products.name_ar', 'like', "%{$term}%")
                  ->orWhere('products.name_en', 'like', "%{$term}%")
                  ->orWhere('products.description_ar', 'like', "%{$term}%")
                  ->orWhere('products.description_en', 'like', "%{$term}%");
            });
        }
        if (!empty($f['category'])) {
            $cat = is_numeric($f['category']) ? (int) $f['category'] : (self::CATEGORY[strtolower((string) $f['category'])] ?? null);
            if ($cat) $q->where('products.category', $cat);
        }
        if (!empty($f['max_price'])) $q->where('products.price', '<=', (float) $f['max_price']);
        if (!empty($f['occasion'])) $q->where('products_meta.occasion', strtolower((string) $f['occasion']));
        if (!empty($f['grade']))    $q->where('products_meta.grade', strtolower((string) $f['grade']));

        $rows = $q->orderByDesc('products.price')->limit(12)->get([
            'products.id', 'products.name_ar', 'products.name_en', 'products.price',
            'products.weight', 'products.countInStock', 'products.slug',
            'products_meta.occasion', 'products_meta.grade',
        ]);

        return $rows->map(fn ($p) => [
            'id' => (int) $p->id,
            'name_ar' => $p->name_ar,
            'name_en' => $p->name_en,
            'price_sar' => (float) $p->price,
            'weight_kg' => (float) $p->weight,
            'in_stock' => (int) $p->countInStock > 0,
            'occasion' => $p->occasion,
            'grade' => $p->grade,
        ])->all();
    }

    public function getProduct($idOrSlug): ?array
    {
        $p = DB::table('products')
            ->leftJoin('products_meta', 'products_meta.product_id', '=', 'products.id')
            ->where(is_numeric($idOrSlug) ? 'products.id' : 'products.slug', $idOrSlug)
            ->first([
                'products.id', 'products.name_ar', 'products.name_en', 'products.price',
                'products.weight', 'products.countInStock', 'products.slug',
                'products.description_ar', 'products.description_en',
                'products_meta.occasion', 'products_meta.grade',
            ]);
        if (!$p) return null;
        return [
            'id' => (int) $p->id,
            'name_ar' => $p->name_ar, 'name_en' => $p->name_en,
            'price_sar' => (float) $p->price, 'weight_kg' => (float) $p->weight,
            'in_stock' => (int) $p->countInStock > 0, 'stock_qty' => (int) $p->countInStock,
            'description_ar' => $p->description_ar, 'occasion' => $p->occasion, 'grade' => $p->grade,
        ];
    }

    public function shippingFor(float $subtotal): array
    {
        $free = $subtotal >= self::FREE_THRESHOLD;
        return [
            'fee_sar' => $free ? 0.0 : self::SHIPPING_FEE,
            'free' => $free,
            'free_threshold_sar' => self::FREE_THRESHOLD,
            'eta_days' => self::ETA_DAYS,
            'ksa_only' => true,
        ];
    }

    /**
     * Create an order from the agent. Items are [{product_id, qty}]; everything is
     * priced server-side from the DB. Returns [order_id, total_sar, pay_url] or an
     * 'error' string. Idempotent within a conversation for ~30 min on identical carts.
     *
     * @param array $items     [['product_id'=>int,'qty'=>int], ...]
     * @param array $customer  ['name','phone','city','address','email'?]
     */
    public function createOrder(array $items, array $customer, ?int $conversationId = null): array
    {
        $items = array_values(array_filter($items, fn ($i) => !empty($i['product_id']) && (int) ($i['qty'] ?? 0) > 0));
        if (empty($items)) return ['error' => 'No valid items to order.'];

        foreach (['name', 'city', 'address'] as $req) {
            if (empty(trim((string) ($customer[$req] ?? '')))) {
                return ['error' => "Missing customer {$req}. Ask the customer for it before ordering."];
            }
        }
        $phone = trim((string) ($customer['phone'] ?? ''));
        if ($phone === '') return ['error' => 'Missing customer phone.'];

        // Price + stock-check from the DB.
        $lines = [];
        $itemsPrice = 0.0; $weight = 0.0;
        foreach ($items as $i) {
            $p = DB::table('products')->where('id', (int) $i['product_id'])->first(['id', 'name_ar', 'price', 'weight', 'countInStock']);
            if (!$p) return ['error' => "Product {$i['product_id']} not found."];
            $qty = (int) $i['qty'];
            if ((int) $p->countInStock < $qty) {
                return ['error' => "Not enough stock for {$p->name_ar} (only {$p->countInStock} left). Tell the customer."];
            }
            $itemsPrice += (float) $p->price * $qty;
            $weight     += (float) $p->weight * $qty;
            $lines[] = ['id' => (int) $p->id, 'qty' => $qty, 'price' => (float) $p->price, 'weight' => (float) $p->weight];
        }

        $ship = $this->shippingFor($itemsPrice);
        $shippingPrice = $ship['fee_sar'];
        $totalPrice = round($itemsPrice + $shippingPrice, 2);
        $taxPrice   = round($totalPrice * 15 / 115, 2);

        // Idempotency: same conversation, still unpaid, same cart, last 30 min → reuse.
        if ($conversationId) {
            $recent = DB::table('orders')
                ->where('conversation_id', $conversationId)->where('isPaid', 0)
                ->where('created_at', '>=', now()->subMinutes(30))
                ->orderByDesc('id')->first();
            if ($recent && abs((float) $recent->totalPrice - $totalPrice) < 0.01) {
                return [
                    'order_id' => (int) $recent->id,
                    'total_sar' => (float) $recent->totalPrice,
                    'pay_url' => $this->storeUrl() . '/pay/' . $recent->id,
                    'reused' => true,
                ];
            }
        }

        $orderId = Order::insertGetId([
            'user_id'       => null,
            'name'          => trim((string) $customer['name']),
            'email'         => trim((string) ($customer['email'] ?? '')) ?: '',
            'phone'         => $phone,
            'country'       => 'SA',
            'city'          => trim((string) $customer['city']),
            'paymentMethod' => 'online',
            'address'       => trim((string) $customer['address']),
            'itemsPrice'    => $itemsPrice,
            'taxPrice'      => $taxPrice,
            'shippingPrice' => $shippingPrice,
            'totalPrice'    => $totalPrice,
            'weight'        => $weight,
            'discount'      => 0,
            'source'        => 'whatsapp',
            'conversation_id' => $conversationId,
            'created_at'    => now(),
        ]);

        foreach ($lines as $l) {
            OrderItem::insert([
                'order_id' => $orderId, 'product_id' => $l['id'],
                'qty' => $l['qty'], 'price' => $l['price'], 'weight' => $l['weight'],
            ]);
        }

        return [
            'order_id' => (int) $orderId,
            'subtotal_sar' => $itemsPrice,
            'shipping_sar' => $shippingPrice,
            'total_sar' => $totalPrice,
            'pay_url' => $this->storeUrl() . '/pay/' . $orderId,
        ];
    }
}

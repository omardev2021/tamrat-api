<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * One row per meaningful signal in a WhatsApp commerce conversation.
 * The reusable schema behind the data flywheel — see the data_flywheel migration.
 */
class CommerceEvent extends Model
{
    public $timestamps = false; // we only keep created_at

    protected $fillable = [
        'conversation_id', 'customer_ref', 'type', 'occasion', 'category',
        'product_id', 'order_id', 'price_point', 'converted', 'lang', 'query', 'meta', 'created_at',
    ];

    protected $casts = [
        'converted' => 'boolean',
        'meta' => 'array',
    ];

    /** Best-effort logger — never breaks the caller. */
    public static function record(array $attrs): void
    {
        try {
            $attrs['created_at'] = $attrs['created_at'] ?? now();
            static::create($attrs);
        } catch (\Throwable $e) {
            Log::warning('[CommerceEvent] log failed: ' . $e->getMessage());
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fulfillment handoff fields. The orders table already carries `awb` (tracking
 * number) and `isDelivered` from 2023 but nothing ever populated them — this adds
 * the workflow state around them:
 *
 *  - fulfillment_status: pending | processing | shipped | delivered | cancelled.
 *    A paid order enters 'pending' (the fulfillment queue). 'awb' holds the
 *    tracking number once shipped.
 *  - carrier: shipping company name (e.g. SMSA, Aramex) — free text so any
 *    courier works without a code change; a carrier→tracking-URL map lives in
 *    config for the ones we know.
 *  - shipped_at / delivered_at: timestamps for SLA visibility.
 *  - fulfillment_notified_at: guardrail so the "your order shipped" notification
 *    fires exactly once even if mark-shipped is called twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'fulfillment_status')) {
                $table->string('fulfillment_status')->nullable()->index();
            }
            if (!Schema::hasColumn('orders', 'carrier')) {
                $table->string('carrier')->nullable();
            }
            if (!Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'fulfillment_notified_at')) {
                $table->timestamp('fulfillment_notified_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['fulfillment_status', 'carrier', 'shipped_at', 'delivered_at', 'fulfillment_notified_at'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

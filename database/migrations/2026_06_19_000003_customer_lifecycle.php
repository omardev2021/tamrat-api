<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer lifecycle / retention engine (moat layer 3: customer graph + retention).
 * One row per customer (keyed by phone) with purchase cadence, predicted next
 * reorder, and WhatsApp opt-in — the brain behind predictive replenishment and
 * occasion nudges. Rebuilt from paid orders by LifecycleService.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_lifecycle')) return;
        Schema::create('customer_lifecycle', function (Blueprint $table) {
            $table->id();
            $table->string('customer_ref', 32)->unique();   // last 9 digits of phone
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('orders_count')->default(0);
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->decimal('total_spent', 19, 2)->default(0);
            $table->unsignedInteger('avg_interval_days')->nullable(); // null = not enough orders
            $table->timestamp('predicted_next_at')->nullable()->index();
            $table->string('top_category')->nullable();
            $table->boolean('wa_opt_in')->default(false)->index(); // true once they've bought via WhatsApp
            $table->timestamp('last_nudge_at')->nullable();
            $table->string('last_nudge_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_lifecycle');
    }
};

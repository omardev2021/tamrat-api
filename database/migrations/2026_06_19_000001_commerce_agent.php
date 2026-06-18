<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp buying agent (Watar Commerce Agent) — Phase 1.
 *  - orders.source: where the order originated ('web' | 'whatsapp')
 *  - orders.conversation_id: Chatwoot conversation that created it (for the
 *    "paid ✅" push back into the same WhatsApp thread)
 *  - products_meta: occasion/grade overlay so the agent can match
 *    "وش يصلح كهدية؟" / premium requests to the right products.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'source')) {
                $table->string('source')->default('web')->index();
            }
            if (!Schema::hasColumn('orders', 'conversation_id')) {
                $table->unsignedBigInteger('conversation_id')->nullable()->index();
            }
        });

        if (!Schema::hasTable('products_meta')) {
            Schema::create('products_meta', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_id')->unique();
                $table->string('occasion')->nullable(); // gift | daily | ramadan | luxury | family
                $table->string('grade')->nullable();     // premium | standard | luxury
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'source')) $table->dropColumn('source');
            if (Schema::hasColumn('orders', 'conversation_id')) $table->dropColumn('conversation_id');
        });
        Schema::dropIfExists('products_meta');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data flywheel (Watar Commerce Agent — the moat seed).
 * Logs the structured "exhaust" of every WhatsApp commerce conversation:
 * what people search for, by occasion/variety/price, what objections kill the
 * sale, and what converts — in a reusable schema. Cheap to capture now; it
 * compounds the moment merchant #2..N arrive. Throw nothing away.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commerce_events')) return;
        Schema::create('commerce_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->string('customer_ref', 32)->nullable()->index(); // last 9 digits of phone — journey key
            $table->string('type', 32)->index();      // search | product_view | order_created | paid | objection
            $table->string('occasion')->nullable()->index(); // gift | daily | ramadan | luxury | family
            $table->string('category')->nullable();   // variety / category
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->decimal('price_point', 19, 2)->nullable(); // budget signal (e.g. max_price)
            $table->boolean('converted')->default(false)->index();
            $table->string('lang', 8)->nullable();    // ar | en
            $table->text('query')->nullable();        // raw intent text (carries dialect)
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_events');
    }
};

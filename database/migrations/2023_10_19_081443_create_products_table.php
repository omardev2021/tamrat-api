<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('origin_en');
            $table->string('origin_ar');
            $table->string('image_path');
            $table->string('slug');
            $table->integer('category');
            $table->longText('description_en');
            $table->longText('description_ar');
            $table->decimal('price',19,2);
            $table->decimal('weight',19,2);
            $table->decimal('rating',19,2);
            $table->integer('numReviews');
            $table->integer('countInStock');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */                                                          

    public function down(): void
    {
        Schema::dropIfExists('products');
    }

};

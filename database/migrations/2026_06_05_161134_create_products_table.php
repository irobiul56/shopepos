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
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained();
            $table->foreignId('brand_id')->nullable()->constrained();
            $table->foreignId('unit_id')->constrained();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique(); // Stock Keeping Unit
            $table->string('barcode')->nullable()->unique();
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->decimal('wholesale_price', 12, 2)->nullable(); // পাইকারি দাম
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_alert')->default(5); // নূন্যতম স্টক সতর্কতা
            $table->string('product_image')->nullable();
            $table->json('gallery_images')->nullable(); // একাধিক ছবি
            $table->boolean('is_active')->default(true);
            $table->boolean('is_taxable')->default(true);
            $table->decimal('tax_rate', 5, 2)->default(0); // ভ্যাট/ট্যাক্স হার
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

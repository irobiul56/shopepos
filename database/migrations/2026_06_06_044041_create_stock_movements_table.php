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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->enum('movement_type', ['purchase', 'sale', 'return', 'damage', 'adjustment']);
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->string('reference_no')->nullable(); // invoice_no বা অন্য রেফারেন্স
            $table->foreignId('user_id')->constrained();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

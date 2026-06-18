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
        Schema::create('shops', function (Blueprint $table) {
             $table->id();
            $table->string('name');
            $table->string('owner_name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('address');
            $table->enum('shop_type', [
                'grocery', 
                'clothing', 
                'pharmacy', 
                'tailor', 
                'electronics', 
                'restaurant', 
                'other'
            ])->default('other');
            $table->string('logo')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('currency_symbol')->default('$');
            $table->string('currency_code')->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};

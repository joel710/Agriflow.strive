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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Or set null on product delete if order history needs to be kept even if product is removed from sale
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Price at the time of order
            $table->decimal('total_price', 10, 2); // quantity * unit_price
            $table->timestamps(); // Optional, if item-specific timing is needed beyond order timing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

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
            $table->foreignId('producer_id')->constrained('producers')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('unit'); // e.g., kg, piece, litre
            $table->integer('stock_quantity')->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_bio')->default(false);
            $table->boolean('is_available')->default(true);
            // Categories could be a separate table or a JSON field if more complex filtering is needed.
            // For now, keeping it simple or assuming it's part of description/tags.
            // If a dedicated category text field is needed: $table->string('category')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('is_bio');
            $table->index('is_available');
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

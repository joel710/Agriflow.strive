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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['en_attente', 'confirmee', 'en_preparation', 'en_livraison', 'livree', 'annulee'])->default('en_attente');
            $table->enum('payment_status', ['en_attente', 'payee', 'remboursee', 'echec'])->default('en_attente');
            $table->string('payment_method')->nullable(); // e.g., 'PayGate', 'TMoney', 'Moov', 'wallet'
            $table->text('delivery_address'); // Copied from customer at time of order, or specific for this order
            $table->text('delivery_notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

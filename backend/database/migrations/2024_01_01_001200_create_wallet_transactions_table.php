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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable(); // e.g., "Payment for order #123", "Refund for order #456", "Producer payout"

            // Optional: For linking directly to the source of the transaction (e.g., an Order, a Payout record)
            // $table->nullableMorphs('related'); // This would add related_id (BIGINT UNSIGNED) and related_type (VARCHAR)
            // Example of a more specific link if only orders are related:
            // $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');

            $table->timestamps();

            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

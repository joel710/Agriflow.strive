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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->onDelete('cascade');
            $table->enum('status', ['en_attente', 'en_preparation', 'en_cours', 'livree', 'annulee', 'echec_livraison'])->default('en_attente');
            $table->string('tracking_number')->nullable()->unique();
            $table->timestamp('estimated_delivery_date')->nullable();
            $table->timestamp('actual_delivery_date')->nullable();
            $table->string('delivery_person_name')->nullable(); // Could be a FK to a 'drivers' table if complex
            $table->string('delivery_person_phone')->nullable();
            $table->text('delivery_notes')->nullable(); // Notes specific to this delivery attempt
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};

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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            // A wallet belongs to a user.
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0.00); // Increased precision for larger balances
            $table->string('currency', 3)->default('XOF'); // ISO 4217 currency code
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};

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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->boolean('notification_email')->default(true);
            $table->boolean('notification_sms')->default(false);
            // 'notification_push' might be more complex, depending on implementation (e.g., web push, mobile push)
            // For now, a boolean toggle.
            $table->boolean('notification_app')->default(true); // Renamed from notification_push for clarity
            $table->string('language')->default('fr'); // e.g., 'fr', 'en'
            $table->string('theme')->default('light'); // e.g., 'light', 'dark'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};

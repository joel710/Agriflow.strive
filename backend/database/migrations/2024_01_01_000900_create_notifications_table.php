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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Standard Laravel notification ID
            $table->string('type'); // Class name of the notification e.g., App\Notifications\NewOrder
            $table->morphs('notifiable'); // Creates notifiable_id (BIGINT UNSIGNED) and notifiable_type (VARCHAR)
            $table->text('data'); // JSON encoded data for the notification
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

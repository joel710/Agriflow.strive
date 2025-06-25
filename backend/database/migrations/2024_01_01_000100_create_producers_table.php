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
        Schema::create('producers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('farm_name');
            $table->string('siret', 14)->nullable()->unique();
            $table->integer('experience_years')->nullable();
            $table->enum('farm_type', ['cultures', 'elevage', 'mixte'])->nullable();
            $table->decimal('surface_hectares', 10, 2)->nullable();
            $table->text('farm_address')->nullable();
            $table->text('certifications')->nullable(); // Could be JSON or a separate table if complex
            $table->enum('delivery_availability', ['3j', '5j', '7j'])->nullable();
            $table->text('farm_description')->nullable();
            $table->string('farm_photo_url')->nullable();
            $table->timestamps();

            $table->index('farm_name');
            $table->index('farm_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producers');
    }
};

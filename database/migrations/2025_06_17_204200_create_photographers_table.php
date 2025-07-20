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
        Schema::create('photographers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Increased length for names
            $table->string('last_name', 100); // Increased length for last names
            $table->string('phone_number', 15); // Standard length for phone numbers
            $table->string('department', 100); // Increased length for department names
            $table->string('city', 100); // Increased length for city names
            $table->string('personal_description', 255); // Increased length for descriptions
            $table->time('start_time_of_attention'); // Standard format for time (HH:mm)
            $table->time('end_time_of_attention'); // Standard format for time (HH:mm)
            $table->decimal('price_per_hour', 8, 2); // Default price per hour
            $table->string('profile_picture', 255); // Increased length for file paths
            $table->boolean('active')->default(true); // Default to true for active status
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photographers');
    }
};

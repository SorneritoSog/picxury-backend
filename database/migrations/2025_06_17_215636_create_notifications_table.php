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
            $table->id();
            $table->string('type', 50); // Type of notification (e.g., 'session_request', 'session_update'')
            $table->foreignId('photo_session_id')->constrained('photo_sessions')->onDelete('cascade'); // Foreign key to photo_session_types table
            $table->string('title', 100); // Title of the notification
            $table->boolean('is_read')->default(false); // Indicates if the notification has been read
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

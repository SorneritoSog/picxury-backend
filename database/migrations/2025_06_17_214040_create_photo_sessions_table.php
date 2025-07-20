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
        Schema::create('photo_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photographer_id')->constrained('photographers')->onDelete('cascade'); // Foreign key to client table
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade'); // Foreign key to client table
            $table->string('status')->default('Solicitud'); // Status of the photo session
            $table->string('title', 100); // Title of the photo session
            $table->date('date'); // Date of the photo session
            $table->time('start_time'); // Start time of the photo session
            $table->time('end_time'); // End time of the photo session
            $table->string('department', 100); // Department where the photo session takes place
            $table->string('city', 100); // Municipality where the photo session takes place
            $table->string('address', 255); // Address of the photo session
            $table->string('place_description', 255)->nullable(); // Description of the place for the photo session
            $table->decimal('total_price', 10, 2); // Total price of the photo session
            $table->string('payment_status')->default('Pendiente');; // Payment status of the photo session
            $table->foreignId('photo_session_type_id')->constrained('photo_session_types')->onDelete('cascade'); // Foreign key to photo_session_types table
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_sessions');
    }
};

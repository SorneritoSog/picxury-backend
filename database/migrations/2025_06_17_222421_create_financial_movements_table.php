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
        Schema::create('financial_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photographer_id')->constrained('photographers')->onDelete('cascade'); // Foreign key to client table
            $table->string('type', 50); // Type of financial movement, e.g., "income", "expense"
            $table->string('category', 100); // Category of the financial movement, e.g., "transport", "sale", "services", etc.
            $table->decimal('amount', 10, 2); // Amount of the financial movement
            $table->string('detail', 255)->nullable(); // Detail of the financial movement
            $table->foreignId('photo_session_id')->nullable()->constrained('photo_sessions')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_movements');
    }
};

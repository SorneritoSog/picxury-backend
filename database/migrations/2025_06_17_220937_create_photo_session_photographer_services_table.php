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
        Schema::create('photo_session_photographer_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('photo_session_id')->constrained('photo_sessions')->onDelete('cascade'); 
            $table->unsignedBigInteger('photographer_service_id');
            $table->foreign('photographer_service_id', 'photographer_service_id')
                ->references('id')
                ->on('photographer_services')
                ->onDelete('cascade');
            $table->integer('quantity'); // Cantiad del servico del fotografo comprado
            $table->decimal('unit_price', 10, 2); // Precio unitario del servicio del fotografo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_session_photographer_services');
    }
};

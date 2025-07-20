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
        Schema::create('photographer_social_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Name of the social network
            $table->string('url', 255)->nullable(); // URL of the social network profile
            $table->foreignId('photographer_id')->constrained('photographers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photographer_social_networks');
    }
};

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
        Schema::create('photo_session_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Name of the photo session type
            $table->text('description')->nullable(); // Description of the photo session type
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photo_session_types');
    }
};

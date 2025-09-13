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
  Schema::create('driver_vehicle', function (Blueprint $table) {
    $table->id();
    $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
    $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // required
    $table->timestamps();

    // Prevent duplicates
    $table->unique(['driver_id', 'vehicle_id']);
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

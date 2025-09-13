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
Schema::create('vehicles', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('registration_number')->nullable();
    $table->enum('vehicle_type', ['car', 'van', 'truck', 'bus'])->default('car');
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->softDeletes();
    $table->timestamps();
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

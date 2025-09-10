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
        Schema::create('trips', function (Blueprint $table) {
    $table->id();

    // Denormalized for fast filtering
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();

    $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
    $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
    $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
    $table->enum('vehicle_type', ['car', 'van', 'truck' , 'Bus'])->default('Car'); // denormalized for quick filtering
    

    $table->dateTime('start_time');
    $table->dateTime('end_time');
    $table->text('description')->nullable();
    $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');
      
    $table->timestamps();
     $table->softDeletes(); 
    // Optimize overlap + availability queries
    $table->index(['driver_id', 'start_time', 'end_time']);
    $table->index(['vehicle_id', 'start_time', 'end_time']);
    $table->index('company_id');
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

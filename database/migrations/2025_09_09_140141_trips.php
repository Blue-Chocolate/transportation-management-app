<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('trips', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
    $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
    $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
    $table->enum('vehicle_type', ['car', 'van', 'truck', 'bus'])->nullable()->default('car');
    $table->dateTime('start_time');
    $table->dateTime('end_time');
    $table->text('description')->nullable();
    $table->enum('status', ['planned','active','completed','cancelled'])->default('planned');
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index(['driver_id', 'start_time', 'end_time']);
    $table->index(['vehicle_id', 'start_time', 'end_time']);
    $table->index('status');
});

// لاحقًا في migration منفصل (أو في نفس الـ AddTripsTriggersAndConstraints) ضع الـ CHECK constraint:
try {
    DB::statement("ALTER TABLE trips ADD CONSTRAINT chk_trip_times CHECK (start_time < end_time)");
} catch (\Throwable $e) {
    // ignore if already exists
}

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

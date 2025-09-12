<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false; // For triggers

    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            // Removed redundant vehicle_type - derive from vehicle
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->text('description')->nullable();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance (overlap queries)
            $table->index(['driver_id', 'start_time', 'end_time'], 'trips_driver_time_index');
            $table->index(['vehicle_id', 'start_time', 'end_time'], 'trips_vehicle_time_index');
            $table->index('status', 'trips_status_index');
            $table->index('company_id');
        });

        // CHECK constraint for times (MySQL 8+/MariaDB 10.2+)
        try {
            DB::statement("ALTER TABLE trips ADD CONSTRAINT chk_trip_times CHECK (start_time < end_time)");
        } catch (\Throwable $e) {
            // Ignore if not supported
        }

        // Optional: DB Triggers as fallback (but prefer model booted() for Laravel)
        // Uncomment if needed for DB-level enforcement
        
        DB::unprepared('
        CREATE TRIGGER trips_before_insert
        BEFORE INSERT ON trips
        FOR EACH ROW
        BEGIN
          DECLARE cnt INT DEFAULT 0;
          SELECT COUNT(*) INTO cnt FROM trips
            WHERE driver_id = NEW.driver_id
              AND deleted_at IS NULL
              AND status != "cancelled"
              AND NOT (end_time <= NEW.start_time OR start_time >= NEW.end_time);
          IF cnt > 0 THEN
            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Driver has an overlapping trip";
          END IF;
          SELECT COUNT(*) INTO cnt FROM trips
            WHERE vehicle_id = NEW.vehicle_id
              AND deleted_at IS NULL
              AND status != "cancelled"
              AND NOT (end_time <= NEW.start_time OR start_time >= NEW.end_time);
          IF cnt > 0 THEN
            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Vehicle has an overlapping trip";
          END IF;
        END;
        ');

        DB::unprepared('
        CREATE TRIGGER trips_before_update
        BEFORE UPDATE ON trips
        FOR EACH ROW
        BEGIN
          DECLARE cnt INT DEFAULT 0;
          SELECT COUNT(*) INTO cnt FROM trips
            WHERE driver_id = NEW.driver_id
              AND id != NEW.id
              AND deleted_at IS NULL
              AND status != "cancelled"
              AND NOT (end_time <= NEW.start_time OR start_time >= NEW.end_time);
          IF cnt > 0 THEN
            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Driver has an overlapping trip";
          END IF;
          SELECT COUNT(*) INTO cnt FROM trips
            WHERE vehicle_id = NEW.vehicle_id
              AND id != NEW.id
              AND deleted_at IS NULL
              AND status != "cancelled"
              AND NOT (end_time <= NEW.start_time OR start_time >= NEW.end_time);
          IF cnt > 0 THEN
            SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Vehicle has an overlapping trip";
          END IF;
        END;
        ');
        
    }

    public function down(): void
    {
        try {
            DB::unprepared('DROP TRIGGER IF EXISTS trips_before_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS trips_before_update');
        } catch (\Throwable $e) {}
        try {
            DB::statement('ALTER TABLE trips DROP CONSTRAINT chk_trip_times');
        } catch (\Throwable $e) {}
        Schema::dropIfExists('trips');
    }
};
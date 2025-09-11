<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        // 1️⃣ Create the trips table
        // Schema::create('trips', function (Blueprint $table) {
        //     $table->id();

        //     $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
        //     $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
        //     $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();

        //     $table->enum('vehicle_type', ['car', 'van', 'truck', 'bus'])->default('car');

        //     $table->dateTime('start_time');
        //     $table->dateTime('end_time');
        //     $table->text('description')->nullable();
        //     $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');

        //     $table->timestamps();
        //     $table->softDeletes();

        //     // Indexes
        //     $table->index(['driver_id', 'start_time', 'end_time'], 'trips_driver_time_index');
        //     $table->index(['vehicle_id', 'start_time', 'end_time'], 'trips_vehicle_time_index');
        //     $table->index('status', 'trips_status_index');
        // });

        // 2️⃣ Add CHECK constraint (MySQL 8+)
        try {
            DB::statement("ALTER TABLE `trips` ADD CONSTRAINT `chk_trip_times` CHECK (`start_time` < `end_time`)");
        } catch (\Throwable $e) {
            // Ignore if not supported or already exists
        }

        // 3️⃣ Add triggers
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trips_before_insert
BEFORE INSERT ON trips
FOR EACH ROW
BEGIN
  DECLARE cnt INT DEFAULT 0;

  SELECT COUNT(*) INTO cnt FROM trips
    WHERE driver_id = NEW.driver_id
      AND deleted_at IS NULL
      AND NOT (end_time <= NEW.start_time OR start_time >= NEW.end_time);
  IF cnt > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Driver has an overlapping trip';
  END IF;

  SELECT COUNT(*) INTO cnt FROM trips
    WHERE vehicle_id = NEW.vehicle_id
      AND deleted_at IS NULL
      AND NOT (end_time <= NEW.start_time OR start_time >= NEW.end_time);
  IF cnt > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vehicle has an overlapping trip';
  END IF;
END;
SQL
        );

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trips_before_update
BEFORE UPDATE ON trips
FOR EACH ROW
BEGIN
  DECLARE cnt INT DEFAULT 0;

  SELECT COUNT(*) INTO cnt FROM trips
    WHERE driver_id = NEW.driver_id
      AND id != NEW.id
      AND deleted_at IS NULL
      AND NOT (end_time <= NEW.start_time OR start_time >= NEW.end_time);
  IF cnt > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Driver has an overlapping trip';
  END IF;

  SELECT COUNT(*) INTO cnt FROM trips
    WHERE vehicle_id = NEW.vehicle_id
      AND id != NEW.id
      AND deleted_at IS NULL
      AND NOT (end_time <= NEW.start_time OR start_time >= NEW.end_time);
  IF cnt > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vehicle has an overlapping trip';
  END IF;
END;
SQL
        );
    }

    public function down(): void
    {
        try { DB::unprepared('DROP TRIGGER IF EXISTS trips_before_insert'); } catch (\Throwable $e) {}
        try { DB::unprepared('DROP TRIGGER IF EXISTS trips_before_update'); } catch (\Throwable $e) {}
        try { DB::statement('ALTER TABLE `trips` DROP CHECK `chk_trip_times`'); } catch (\Throwable $e) {}

        Schema::dropIfExists('trips');
    }
};

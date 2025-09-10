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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Basic info
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('emergency_contact')->nullable();
 
            // Driver details
            $table->string('license')->nullable();
            $table->date('license_expiration')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('employment_status', ['active', 'inactive'])->default('active');

            // Operational data
            $table->json('route_assignments')->nullable(); // flexible JSON
            $table->decimal('performance_rating', 3, 2)->nullable(); // e.g. 4.75
            $table->boolean('medical_certified')->default(false);
            $table->date('background_check_date')->nullable();

            // Additional fields
            $table->string('profile_photo')->nullable();
            $table->text('notes')->nullable();
            $table->string('insurance_info')->nullable();
            $table->json('training_certifications')->nullable();
                       $table->softDeletes(); 

            $table->timestamps();
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};

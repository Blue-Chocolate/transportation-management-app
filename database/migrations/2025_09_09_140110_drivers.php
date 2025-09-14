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
            $table->string('name');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('license')->nullable();
            $table->date('license_expiration')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('employment_status', ['active', 'inactive'])->default('active');
            $table->json('route_assignments')->nullable();
            $table->decimal('performance_rating', 3, 2)->nullable();
            $table->boolean('medical_certified')->default(false);
            $table->date('background_check_date')->nullable();
            $table->string('profile_photo')->nullable();
            $table->text('notes')->nullable();
            $table->string('insurance_info')->nullable();
            $table->json('training_certifications')->nullable();
            $table->softDeletes();
            $table->timestamps();
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
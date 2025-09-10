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
       Schema::create('clients', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('phone')->nullable();
    $table->string('password');
    $table->timestamps();
    $table->softDeletes(); // adds `deleted_at` column

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

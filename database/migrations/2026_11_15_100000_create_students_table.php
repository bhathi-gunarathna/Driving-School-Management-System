<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('student_number', 50)->unique();
            $table->string('full_name')->index();
            $table->text('address');
            $table->string('nic_number', 30)->unique();
            $table->date('date_of_birth');
            $table->string('contact_number', 25)->index();
            $table->string('medical_report_path')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_vehicle_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_category_id')->constrained()->restrictOnDelete();
            $table->decimal('agreed_fee', 10, 2);
            $table->decimal('required_training_hours', 6, 2);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->unique(['student_id', 'vehicle_category_id'], 'student_vehicle_category_unique');
            $table->index(['student_id', 'status'], 'student_enrollment_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_vehicle_categories');
    }
};

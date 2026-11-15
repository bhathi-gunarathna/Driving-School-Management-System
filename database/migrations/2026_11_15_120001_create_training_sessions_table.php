<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_vehicle_category_id')->constrained('student_vehicle_categories')->restrictOnDelete();
            $table->foreignId('coach_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('coach_assignment_id')->constrained('training_coach_assignments')->restrictOnDelete();
            $table->date('training_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('duration_minutes');
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('completed')->index();
            $table->timestamps();
            $table->index(['student_vehicle_category_id', 'training_date'], 'training_enrollment_date_idx');
            $table->index(['coach_id', 'training_date'], 'training_coach_date_idx');
        });
    }

    public function down(): void { Schema::dropIfExists('training_sessions'); }
};

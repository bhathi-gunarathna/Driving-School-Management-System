<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_coach_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_vehicle_category_id')->constrained('student_vehicle_categories')->restrictOnDelete();
            $table->foreignId('coach_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('assigned_at');
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
            $table->index(['student_vehicle_category_id', 'status'], 'training_assignment_enrollment_status_idx');
            $table->index(['coach_id', 'status'], 'training_assignment_coach_status_idx');
        });
    }

    public function down(): void { Schema::dropIfExists('training_coach_assignments'); }
};

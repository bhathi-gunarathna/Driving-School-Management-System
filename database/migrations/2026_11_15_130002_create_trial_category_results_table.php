<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trial_category_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trial_participant_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_vehicle_category_id')->constrained('student_vehicle_categories')->restrictOnDelete();
            $table->string('result', 20)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();
            $table->unique(['trial_participant_id', 'student_vehicle_category_id'], 'trial_participant_category_unique');
            $table->index(['student_vehicle_category_id', 'result'], 'trial_category_latest_index');
        });
    }
    public function down(): void { Schema::dropIfExists('trial_category_results'); }
};

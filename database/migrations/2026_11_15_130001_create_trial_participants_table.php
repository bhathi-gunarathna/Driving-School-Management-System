<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trial_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trial_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('assigned')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['trial_id', 'student_id'], 'trial_participant_student_unique');
            $table->index(['student_id', 'trial_id'], 'trial_participant_history_index');
        });
    }
    public function down(): void { Schema::dropIfExists('trial_participants'); }
};

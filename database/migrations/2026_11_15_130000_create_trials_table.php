<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trials', function (Blueprint $table): void {
            $table->id();
            $table->string('trial_number', 50)->unique();
            $table->date('trial_date')->index();
            $table->time('reporting_time')->nullable();
            $table->string('location', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('scheduled')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'trial_date'], 'trials_status_date_index');
        });
    }
    public function down(): void { Schema::dropIfExists('trials'); }
};

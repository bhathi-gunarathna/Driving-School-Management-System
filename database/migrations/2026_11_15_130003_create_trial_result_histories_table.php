<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trial_result_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trial_category_result_id')->constrained()->restrictOnDelete();
            $table->string('previous_result', 20)->nullable();
            $table->string('new_result', 20);
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['trial_category_result_id', 'recorded_at'], 'trial_result_audit_index');
        });
    }
    public function down(): void { Schema::dropIfExists('trial_result_histories'); }
};

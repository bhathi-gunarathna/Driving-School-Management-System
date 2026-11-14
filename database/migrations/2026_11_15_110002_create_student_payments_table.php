<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_plan_id')->constrained('student_payment_plans')->restrictOnDelete();
            $table->string('receipt_number', 60)->unique();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date')->index();
            $table->string('payment_method', 30)->index();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'id'], 'student_payment_history_index');
            $table->index(['payment_plan_id', 'id'], 'plan_payment_history_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};

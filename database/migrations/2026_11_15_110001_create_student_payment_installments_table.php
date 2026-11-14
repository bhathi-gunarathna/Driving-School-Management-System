<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payment_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained('student_payment_plans')->restrictOnDelete();
            $table->unsignedTinyInteger('installment_number');
            $table->decimal('planned_amount', 12, 2);
            $table->timestamps();

            $table->unique(['payment_plan_id', 'installment_number'], 'payment_plan_installment_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payment_installments');
    }
};

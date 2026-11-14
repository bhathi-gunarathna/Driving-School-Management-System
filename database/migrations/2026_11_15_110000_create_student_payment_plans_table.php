<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_payment_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('total_agreed_fee', 12, 2);
            $table->string('plan_type', 30);
            $table->unsignedTinyInteger('number_of_installments');
            $table->decimal('total_amount_paid', 12, 2)->default(0);
            $table->decimal('outstanding_balance', 12, 2);
            $table->string('payment_status', 25)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_payment_plans');
    }
};

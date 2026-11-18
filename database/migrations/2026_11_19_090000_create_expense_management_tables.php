<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('payment_method', 30);
            $table->string('paid_to', 160)->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('expense_date', 'expenses_date_idx');
            $table->index(['expense_category_id', 'expense_date'], 'expenses_category_date_idx');
            $table->index(['vehicle_id', 'expense_date'], 'expenses_vehicle_date_idx');
            $table->index(['payment_method', 'expense_date'], 'expenses_method_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('category_code', 20)->unique();
            $table->string('category_name', 150)->index();
            $table->text('description')->nullable();
            $table->decimal('training_hours', 6, 2);
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_categories');
    }
};

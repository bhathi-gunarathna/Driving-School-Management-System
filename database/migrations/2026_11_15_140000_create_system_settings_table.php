<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('school_name')->default('Driving School');
            $table->string('logo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('timezone', 64)->default('Asia/Colombo');
            $table->string('currency', 3)->default('LKR');
            $table->string('date_format', 20)->default('d M Y');
            $table->string('receipt_prefix', 30)->default('DS-RCPT');
            $table->string('student_number_prefix', 20)->default('DS');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

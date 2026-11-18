<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table): void {
                $table->id();
                $table->string('registration_number', 32)->unique();
                $table->string('chassis_number', 100)->nullable()->unique();
                $table->string('engine_number', 100)->nullable()->index();
                $table->foreignId('vehicle_category_id')->nullable()->constrained()->nullOnDelete();
                $table->string('brand', 100);
                $table->string('model', 100);
                $table->unsignedSmallInteger('manufacturing_year')->nullable();
                $table->string('fuel_type', 40);
                $table->string('transmission_type', 40);
                $table->decimal('current_mileage', 10, 1)->default(0);
                $table->date('purchase_date')->nullable();
                $table->string('status', 20)->default('active')->index();
                $table->text('notes')->nullable();
                $table->date('insurance_expiry_date')->nullable()->index();
                $table->string('insurance_document_path')->nullable();
                $table->date('revenue_license_expiry_date')->nullable()->index();
                $table->string('revenue_license_document_path')->nullable();
                $table->timestamps();

                $table->index(['brand', 'model']);
            });
        }

        if (! Schema::hasTable('vehicle_document_renewals')) {
            Schema::create('vehicle_document_renewals', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
                $table->string('document_type', 30);
                $table->string('event_type', 30)->default('renewed');
                $table->date('previous_expiry_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('previous_document_path')->nullable();
                $table->string('document_path')->nullable();
                $table->date('recorded_on');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('document_type', 'vdr_document_type_idx');
                $table->index(['vehicle_id', 'document_type', 'recorded_on'], 'vdr_vehicle_type_recorded_idx');
            });
        }

        // The previous migration attempt may have persisted the tables before
        // failing on MySQL's 64-character index name limit. Add only indexes
        // that are missing, leaving any existing schema and data untouched.
        if (! Schema::hasIndex('vehicle_document_renewals', ['document_type'])) {
            Schema::table('vehicle_document_renewals', function (Blueprint $table): void {
                $table->index('document_type', 'vdr_document_type_idx');
            });
        }

        if (! Schema::hasIndex('vehicle_document_renewals', ['vehicle_id', 'document_type', 'recorded_on'])) {
            Schema::table('vehicle_document_renewals', function (Blueprint $table): void {
                $table->index(['vehicle_id', 'document_type', 'recorded_on'], 'vdr_vehicle_type_recorded_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_document_renewals');
        Schema::dropIfExists('vehicles');
    }
};

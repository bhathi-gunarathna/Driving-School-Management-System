<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FOREIGN_KEY = 'vdr_vehicle_id_fk';

    public function up(): void
    {
        if (! Schema::hasTable('vehicle_document_renewals')) {
            return;
        }

        $vehicleForeignKeys = collect(Schema::getForeignKeys('vehicle_document_renewals'))
            ->filter(fn (array $foreignKey): bool => ($foreignKey['columns'] ?? []) === ['vehicle_id']);

        if ($vehicleForeignKeys->contains(fn (array $foreignKey): bool => in_array(
            strtolower((string) ($foreignKey['on_delete'] ?? '')),
            ['restrict', 'no action'],
            true
        ))) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        Schema::table('vehicle_document_renewals', function (Blueprint $table) use ($vehicleForeignKeys, $driver): void {
            foreach ($vehicleForeignKeys as $foreignKey) {
                $table->dropForeign($driver === 'sqlite' ? ['vehicle_id'] : $foreignKey['name']);
            }

            $table->foreign('vehicle_id', self::FOREIGN_KEY)
                ->references('id')->on('vehicles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Deliberately retain the restrictive constraint on rollback. Restoring
        // cascade deletion would put historical renewal records at risk again.
    }
};

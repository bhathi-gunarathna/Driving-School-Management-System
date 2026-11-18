<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class VehicleDocumentExpiryService
{
    /**
     * A single row per current vehicle document, suitable for filtering and
     * paginating without loading vehicles and their documents individually.
     */
    public function documents(bool $operationalVehiclesOnly = false): Builder
    {
        $insurance = DB::table('vehicles')
            ->select([
                'id as vehicle_id',
                'registration_number',
                'status as vehicle_status',
                DB::raw("'insurance' as document_type"),
                'insurance_expiry_date as expiry_date',
            ])
            ->whereNotNull('insurance_expiry_date');

        $license = DB::table('vehicles')
            ->select([
                'id as vehicle_id',
                'registration_number',
                'status as vehicle_status',
                DB::raw("'revenue_license' as document_type"),
                'revenue_license_expiry_date as expiry_date',
            ])
            ->whereNotNull('revenue_license_expiry_date');

        if ($operationalVehiclesOnly) {
            $insurance->whereIn('status', ['active', 'maintenance']);
            $license->whereIn('status', ['active', 'maintenance']);
        }

        return $insurance->unionAll($license);
    }
}

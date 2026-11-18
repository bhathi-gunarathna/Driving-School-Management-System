<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Models\Vehicle;
use App\Models\VehicleDocumentRenewal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleDocumentController
{
    public function current(Vehicle $vehicle, string $type): StreamedResponse
    {
        $this->authorizeView();
        abort_unless(in_array($type, ['insurance', 'revenue-license'], true), 404);

        $field = $type === 'insurance' ? 'insurance_document_path' : 'revenue_license_document_path';
        $path = $vehicle->{$field};

        return $this->streamManagedDocument($path, $vehicle->getKey(), 'vehicle-document-'.$vehicle->getKey().'-'.$type);
    }

    public function renewal(Request $request, VehicleDocumentRenewal $renewal): StreamedResponse
    {
        $this->authorizeView();
        $renewal->loadMissing('vehicle');
        abort_unless($renewal->vehicle, 404);
        $path = $request->query('version') === 'previous'
            ? $renewal->previous_document_path
            : $renewal->document_path;

        return $this->streamManagedDocument(
            $path,
            $renewal->vehicle_id,
            'vehicle-renewal-'.$renewal->getKey()
        );
    }

    private function authorizeView(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('vehicles.view'), 403);
    }

    private function streamManagedDocument(?string $path, int $vehicleId, string $downloadName): StreamedResponse
    {
        abort_unless(is_string($path)
            && preg_match('/\Avehicle-documents\/'.$vehicleId.'\/[A-Za-z0-9._-]+\z/D', $path) === 1
            && ! str_contains(basename($path), '..'), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        $mimeType = $disk->mimeType($path);
        abort_unless(in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png'], true), 404);

        return $disk->response(
            $path,
            $downloadName.'.'.pathinfo($path, PATHINFO_EXTENSION),
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline',
        );
    }
}

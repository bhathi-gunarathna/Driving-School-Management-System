<?php

namespace App\Livewire\Admin\Vehicles;

use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleDocumentRenewal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class Create extends Component
{
    use WithFileUploads;

    public string $registration_number = '';
    public ?string $chassis_number = '';
    public ?string $engine_number = '';
    public ?string $vehicle_category_id = '';
    public string $brand = '';
    public string $model = '';
    public ?string $manufacturing_year = '';
    public string $fuel_type = 'petrol';
    public string $transmission_type = 'manual';
    public string $current_mileage = '0';
    public ?string $purchase_date = '';
    public string $status = 'active';
    public ?string $notes = '';
    public ?string $insurance_expiry_date = '';
    public ?string $revenue_license_expiry_date = '';
    public $insuranceDocument = null;
    public $revenueLicenseDocument = null;

    public function mount(): void
    {
        $this->authorizeManage();
    }

    protected function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:32', Rule::unique('vehicles', 'registration_number')],
            'chassis_number' => ['nullable', 'string', 'max:100', Rule::unique('vehicles', 'chassis_number')],
            'engine_number' => ['nullable', 'string', 'max:100'],
            'vehicle_category_id' => ['nullable', 'integer', Rule::exists('vehicle_categories', 'id')->where('is_active', true)],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'manufacturing_year' => ['nullable', 'integer', 'between:1950,'.(now()->year + 1)],
            'fuel_type' => ['required', Rule::in(Vehicle::FUEL_TYPES)],
            'transmission_type' => ['required', Rule::in(Vehicle::TRANSMISSION_TYPES)],
            'current_mileage' => ['required', 'numeric', 'min:0', 'decimal:0,1'],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in(Vehicle::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'insurance_expiry_date' => ['nullable', 'date'],
            'revenue_license_expiry_date' => ['nullable', 'date'],
            'insuranceDocument' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'revenueLicenseDocument' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    public function createVehicle()
    {
        $this->authorizeManage();
        $this->normalizeInput();
        $validated = $this->validate();
        $storedPaths = [];

        try {
            $vehicle = DB::transaction(function () use ($validated, &$storedPaths): Vehicle {
                $vehicle = Vehicle::create($this->vehicleAttributes($validated));
                $documentFields = $this->storeDocuments($validated, $vehicle->getKey(), $storedPaths);
                $vehicle->update($documentFields);

                foreach ([
                    'insurance' => ['expiry' => 'insurance_expiry_date', 'path' => 'insurance_document_path'],
                    'revenue_license' => ['expiry' => 'revenue_license_expiry_date', 'path' => 'revenue_license_document_path'],
                ] as $type => $fields) {
                    if ($vehicle->{$fields['expiry']} || $vehicle->{$fields['path']}) {
                        VehicleDocumentRenewal::create([
                            'vehicle_id' => $vehicle->getKey(),
                            'document_type' => $type,
                            'event_type' => 'renewed',
                            'previous_expiry_date' => null,
                            'expiry_date' => $vehicle->{$fields['expiry']}?->toDateString(),
                            'previous_document_path' => null,
                            'document_path' => $vehicle->{$fields['path']},
                            'recorded_on' => today()->toDateString(),
                        ]);
                    }
                }

                return $vehicle;
            });
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($storedPaths);
            throw $exception;
        }

        session()->flash('success', 'Vehicle registered successfully.');

        return $this->redirectRoute('admin.vehicles.view', ['vehicle' => $vehicle->getKey()]);
    }

    private function authorizeManage(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('vehicles.manage'), 403);
    }

    private function normalizeInput(): void
    {
        $this->registration_number = strtoupper(trim($this->registration_number));
        $this->chassis_number = $this->nullableUpper($this->chassis_number);
        $this->engine_number = $this->nullableUpper($this->engine_number);
        $this->brand = trim($this->brand);
        $this->model = trim($this->model);
        $this->vehicle_category_id = $this->nullableString($this->vehicle_category_id);
        $this->manufacturing_year = $this->nullableString($this->manufacturing_year);
        $this->purchase_date = $this->nullableString($this->purchase_date);
        $this->notes = $this->nullableString($this->notes);
        $this->insurance_expiry_date = $this->nullableString($this->insurance_expiry_date);
        $this->revenue_license_expiry_date = $this->nullableString($this->revenue_license_expiry_date);
    }

    private function nullableUpper(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return $value === '' ? null : $value;
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function vehicleAttributes(array $validated): array
    {
        unset($validated['insuranceDocument'], $validated['revenueLicenseDocument']);
        foreach (['chassis_number', 'engine_number', 'vehicle_category_id', 'manufacturing_year', 'purchase_date', 'notes', 'insurance_expiry_date', 'revenue_license_expiry_date'] as $nullableField) {
            if (($validated[$nullableField] ?? '') === '') {
                $validated[$nullableField] = null;
            }
        }

        return $validated;
    }

    private function storeDocuments(array $validated, int $vehicleId, array &$storedPaths): array
    {
        $paths = [];
        foreach ([
            'insuranceDocument' => 'insurance_document_path',
            'revenueLicenseDocument' => 'revenue_license_document_path',
        ] as $uploadField => $pathField) {
            $upload = $validated[$uploadField] ?? null;
            if (! $upload) {
                continue;
            }

            $path = $upload->store('vehicle-documents/'.$vehicleId, 'local');
            if (! is_string($path)) {
                throw new \RuntimeException('The vehicle document could not be stored.');
            }
            $storedPaths[] = $path;
            $paths[$pathField] = $path;
        }

        return $paths;
    }

    private function deleteStoredFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && preg_match('/\Avehicle-documents\/\d+\/[A-Za-z0-9._-]+\z/D', $path) === 1) {
                Storage::disk('local')->delete($path);
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.vehicles.create', [
            'categories' => VehicleCategory::active()->orderBy('category_code')->get(),
            'fuelTypes' => Vehicle::FUEL_TYPES,
            'transmissionTypes' => Vehicle::TRANSMISSION_TYPES,
            'statuses' => Vehicle::STATUSES,
        ])->layout('layouts.admin');
    }
}

<?php

namespace App\Livewire\Admin\Vehicles;

use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleDocumentRenewal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class Edit extends Component
{
    use WithFileUploads;

    public Vehicle $vehicle;
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

    public function mount(Vehicle $vehicle): void
    {
        $this->authorizeManage();
        $this->vehicle = $vehicle->load('vehicleCategory');

        foreach ([
            'registration_number', 'chassis_number', 'engine_number', 'vehicle_category_id', 'brand', 'model',
            'manufacturing_year', 'fuel_type', 'transmission_type', 'current_mileage', 'purchase_date', 'status',
            'notes', 'insurance_expiry_date', 'revenue_license_expiry_date',
        ] as $field) {
            $value = $vehicle->{$field};
            $this->{$field} = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) ($value ?? '');
        }
    }

    protected function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:32', Rule::unique('vehicles', 'registration_number')->ignore($this->vehicle->getKey())],
            'chassis_number' => ['nullable', 'string', 'max:100', Rule::unique('vehicles', 'chassis_number')->ignore($this->vehicle->getKey())],
            'engine_number' => ['nullable', 'string', 'max:100'],
            'vehicle_category_id' => ['nullable', 'integer', 'exists:vehicle_categories,id'],
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

    public function updateVehicle()
    {
        $this->authorizeManage();
        $this->normalizeInput();
        $validated = $this->validate();
        $vehicleId = $this->vehicle->getKey();
        $storedPaths = [];

        try {
            DB::transaction(function () use ($validated, $vehicleId, &$storedPaths): void {
                $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicleId);
                $newCategoryId = $validated['vehicle_category_id'] ?? null;

                if ($newCategoryId && (string) $newCategoryId !== (string) $vehicle->vehicle_category_id
                    && ! VehicleCategory::active()->whereKey($newCategoryId)->exists()) {
                    throw ValidationException::withMessages([
                        'vehicle_category_id' => 'Only active vehicle categories can be newly assigned.',
                    ]);
                }

                $previous = [];
                foreach ([
                    'insurance' => ['expiry' => 'insurance_expiry_date', 'path' => 'insurance_document_path', 'upload' => 'insuranceDocument'],
                    'revenue_license' => ['expiry' => 'revenue_license_expiry_date', 'path' => 'revenue_license_document_path', 'upload' => 'revenueLicenseDocument'],
                ] as $type => $fields) {
                    $previous[$type] = [
                        'expiry' => $vehicle->{$fields['expiry']}?->toDateString(),
                        'path' => $vehicle->{$fields['path']},
                    ];
                }

                $attributes = $this->vehicleAttributes($validated);
                $vehicle->fill($attributes);

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
                    $vehicle->{$pathField} = $path;
                }

                $vehicle->save();

                foreach ([
                    'insurance' => ['expiry' => 'insurance_expiry_date', 'path' => 'insurance_document_path', 'upload' => 'insuranceDocument'],
                    'revenue_license' => ['expiry' => 'revenue_license_expiry_date', 'path' => 'revenue_license_document_path', 'upload' => 'revenueLicenseDocument'],
                ] as $type => $fields) {
                    $newExpiry = $vehicle->{$fields['expiry']}?->toDateString();
                    $newPath = $vehicle->{$fields['path']};
                    $dateChanged = $previous[$type]['expiry'] !== $newExpiry;
                    $fileChanged = (bool) ($validated[$fields['upload']] ?? null);

                    if (! $dateChanged && ! $fileChanged) {
                        continue;
                    }

                    VehicleDocumentRenewal::create([
                        'vehicle_id' => $vehicleId,
                        'document_type' => $type,
                        'event_type' => $dateChanged && $newExpiry ? 'renewed' : ($fileChanged ? 'document_replaced' : 'updated'),
                        'previous_expiry_date' => $previous[$type]['expiry'],
                        'expiry_date' => $newExpiry,
                        'previous_document_path' => $previous[$type]['path'],
                        'document_path' => $newPath,
                        'recorded_on' => today()->toDateString(),
                    ]);
                }

                $this->vehicle = $vehicle;
            });
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($storedPaths);
            throw $exception;
        }

        $this->insuranceDocument = null;
        $this->revenueLicenseDocument = null;
        session()->flash('success', 'Vehicle details updated successfully.');

        return $this->redirectRoute('admin.vehicles.view', ['vehicle' => $vehicleId]);
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

        return $validated;
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
        $categories = VehicleCategory::query()->where(function ($query): void {
            $query->where('is_active', true)->orWhereKey($this->vehicle->vehicle_category_id ?? 0);
        })->orderBy('category_code')->get();

        return view('livewire.admin.vehicles.edit', [
            'categories' => $categories,
            'fuelTypes' => Vehicle::FUEL_TYPES,
            'transmissionTypes' => Vehicle::TRANSMISSION_TYPES,
            'statuses' => Vehicle::STATUSES,
        ])->layout('layouts.admin');
    }
}

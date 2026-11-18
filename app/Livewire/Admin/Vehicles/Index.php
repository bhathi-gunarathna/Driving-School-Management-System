<?php

namespace App\Livewire\Admin\Vehicles;

use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $categoryFilter = 'all';

    public function mount(): void
    {
        $this->authorizeView();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(string $value): void
    {
        if (! in_array($value, ['all', ...Vehicle::STATUSES], true)) {
            $this->statusFilter = 'all';
        }
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function setStatus(int $vehicleId, string $status): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('vehicles.manage'), 403);
        abort_unless(in_array($status, Vehicle::STATUSES, true), 422);

        DB::transaction(function () use ($vehicleId, $status): void {
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($vehicleId);
            $vehicle->status = $status;
            $vehicle->save();
        });

        session()->flash('success', 'Vehicle status updated.');
    }

    private function authorizeView(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('vehicles.view'), 403);
    }

    public function render()
    {
        $totalVehicles = Vehicle::query()->count();
        $activeVehicles = Vehicle::query()->where('status', 'active')->count();
        $maintenanceVehicles = Vehicle::query()->where('status', 'maintenance')->count();
        $inactiveVehicles = Vehicle::query()->where('status', 'inactive')->count();

        $vehicles = Vehicle::query()
            ->with('vehicleCategory')
            ->when(trim($this->search) !== '', function ($query): void {
                $search = '%'.trim($this->search).'%';
                $query->where(function ($nested) use ($search): void {
                    $nested->where('registration_number', 'like', $search)
                        ->orWhere('chassis_number', 'like', $search)
                        ->orWhere('engine_number', 'like', $search)
                        ->orWhere('brand', 'like', $search)
                        ->orWhere('model', 'like', $search);
                });
            })
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== 'all', fn ($query) => $query->where('vehicle_category_id', (int) $this->categoryFilter))
            ->orderBy('registration_number')
            ->paginate(10);

        return view('livewire.admin.vehicles.index', [
            'vehicles' => $vehicles,
            'categories' => VehicleCategory::orderBy('category_code')->get(),
            'statuses' => Vehicle::STATUSES,
            'totalVehicles' => $totalVehicles,
            'activeVehicles' => $activeVehicles,
            'maintenanceVehicles' => $maintenanceVehicles,
            'inactiveVehicles' => $inactiveVehicles,
        ])->layout('layouts.admin');
    }
}

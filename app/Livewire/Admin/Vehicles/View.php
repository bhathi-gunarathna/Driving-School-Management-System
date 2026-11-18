<?php

namespace App\Livewire\Admin\Vehicles;

use App\Models\Vehicle;
use Livewire\Component;

class View extends Component
{
    public Vehicle $vehicle;

    public function mount(Vehicle $vehicle): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('vehicles.view'), 403);

        $this->vehicle = $vehicle->load(['vehicleCategory', 'documentRenewals']);
    }

    public function render()
    {
        return view('livewire.admin.vehicles.view')
            ->layout('layouts.admin');
    }
}

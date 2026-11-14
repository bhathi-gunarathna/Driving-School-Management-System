<?php

namespace App\Livewire\Admin\VehicleCategories;

use App\Models\VehicleCategory;
use Livewire\Component;

class View extends Component
{
    public VehicleCategory $category;

    public function mount(VehicleCategory $category): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('vehicle-categories.view'), 403);

        $this->category = $category;
    }

    public function render()
    {
        return view('livewire.admin.vehicle-categories.view')
            ->layout('layouts.admin');
    }
}

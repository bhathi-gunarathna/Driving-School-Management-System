<?php

namespace App\Livewire\Admin\VehicleCategories;

use App\Models\VehicleCategory;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('vehicle-categories.view'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(string $value): void
    {
        if (! in_array($value, ['all', 'active', 'inactive'], true)) {
            $this->statusFilter = 'all';
        }

        $this->resetPage();
    }

    public function toggleActive(int $categoryId): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('vehicle-categories.manage'), 403);

        DB::transaction(function () use ($categoryId): void {
            $category = VehicleCategory::query()
                ->lockForUpdate()
                ->findOrFail($categoryId);

            $category->is_active = ! $category->is_active;
            $category->save();
        });

        session()->flash('success', 'Vehicle category status updated.');
    }

    public function render()
    {
        $totalCategories = VehicleCategory::query()->count();
        $activeCategories = VehicleCategory::query()->where('is_active', true)->count();
        $inactiveCategories = VehicleCategory::query()->where('is_active', false)->count();

        $categories = VehicleCategory::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($nestedQuery): void {
                    $search = '%'.$this->search.'%';
                    $nestedQuery->where('category_code', 'like', $search)
                        ->orWhere('category_name', 'like', $search);
                });
            })
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('category_code')
            ->paginate(10);

        return view('livewire.admin.vehicle-categories.index', [
            'categories' => $categories,
            'totalCategories' => $totalCategories,
            'activeCategories' => $activeCategories,
            'inactiveCategories' => $inactiveCategories,
        ])->layout('layouts.admin');
    }
}

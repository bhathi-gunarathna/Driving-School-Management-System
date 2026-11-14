<?php

namespace App\Livewire\Admin\Coaches;

use App\Models\User;
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

        abort_unless($user?->hasRole('Owner') && $user->can('coaches.view'), 403);
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

    public function toggleActive(int $coachId): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('coaches.manage'), 403);

        DB::transaction(function () use ($coachId, $user): void {
            $coach = User::role('Coach')
                ->lockForUpdate()
                ->findOrFail($coachId);

            abort_if(
                $coach->is_active && (string) $coach->getKey() === (string) $user->getAuthIdentifier(),
                403,
                'You cannot deactivate your own account.'
            );

            $coach->is_active = ! $coach->is_active;
            $coach->save();
        });

        session()->flash('success', 'Coach account status updated.');
    }

    public function render()
    {
        $baseQuery = User::role('Coach');

        // Statistics
        $totalCoaches = (clone $baseQuery)->count();

        $activeCoaches = (clone $baseQuery)
            ->where('is_active', true)
            ->count();

        $inactiveCoaches = (clone $baseQuery)
            ->where('is_active', false)
            ->count();

        // Coaches Query
        $coaches = User::role('Coach')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($this->statusFilter === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.coaches.index', [
            'coaches' => $coaches,
            'totalCoaches' => $totalCoaches,
            'activeCoaches' => $activeCoaches,
            'inactiveCoaches' => $inactiveCoaches,
        ])->layout('layouts.admin');
    }
}

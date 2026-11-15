<?php

namespace App\Livewire\Admin\Trials;

use App\Models\Trial;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public string $search = '';
    public string $statusFilter = '';
    public string $dateFilter = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('trials.view'), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedDateFilter(): void { $this->resetPage(); }

    public function render()
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('trials.view'), 403);
        $query = Trial::query()->withCount('participants')
            ->when(trim($this->search) !== '', fn (Builder $q) => $q->where(fn (Builder $sub) => $sub->where('trial_number', 'like', '%'.trim($this->search).'%')->orWhere('location', 'like', '%'.trim($this->search).'%')))
            ->when($this->statusFilter === 'upcoming', fn (Builder $q) => $q->upcoming())
            ->when(in_array($this->statusFilter, [Trial::STATUS_COMPLETED, Trial::STATUS_CANCELLED], true), fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFilter !== '', fn (Builder $q) => $q->whereDate('trial_date', $this->dateFilter))
            ->orderByDesc('trial_date')->orderByDesc('id');

        return view('livewire.admin.trials.index', [
            'trials' => $query->paginate(10),
            'upcomingCount' => Trial::upcoming()->count(),
            'completedCount' => Trial::where('status', Trial::STATUS_COMPLETED)->count(),
            'participantCount' => \App\Models\TrialParticipant::count(),
        ])->layout('layouts.admin');
    }
}

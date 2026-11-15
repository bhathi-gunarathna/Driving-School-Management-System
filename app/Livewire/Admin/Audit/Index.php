<?php

namespace App\Livewire\Admin\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $userFilter = '';
    public string $moduleFilter = '';
    public string $actionFilter = '';
    public string $startDate = '';
    public string $endDate = '';

    public function mount(): void { $this->authorizeAudit(); }
    private function authorizeAudit(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('audit-logs.view'), 403);
    }
    public function updated(string $property): void { $this->resetPage(); }

    public function render()
    {
        $this->authorizeAudit();
        $logs = AuditLog::query()
            ->when($this->userFilter !== '', fn ($query) => $query->where('user_id', $this->userFilter))
            ->when($this->moduleFilter !== '', fn ($query) => $query->where('module', $this->moduleFilter))
            ->when($this->actionFilter !== '', fn ($query) => $query->where('action', $this->actionFilter))
            ->when($this->startDate !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->endDate))
            ->when(trim($this->search) !== '', fn ($query) => $query->where(function ($matches): void {
                $term = '%'.trim($this->search).'%';
                $matches->where('description', 'like', $term)->orWhere('user_name', 'like', $term)
                    ->orWhere('related_type', 'like', $term)->orWhere('related_id', 'like', $term);
            }))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(20);

        return view('livewire.admin.audit.index', [
            'logs' => $logs,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ])->layout('layouts.admin');
    }
}

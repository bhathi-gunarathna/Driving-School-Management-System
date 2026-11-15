<?php

namespace App\Livewire\Coach\Training;

use App\Models\StudentVehicleCategory;
use App\Models\User;
use App\Services\TrainingProgress;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public string $search = '';
    public string $progressFilter = '';

    public function mount(): void { $this->authorizeCoach(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedProgressFilter(): void { $this->resetPage(); }

    private function authorizeCoach(): User
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Coach') && $user->is_active && $user->can('training.view_assigned'), 403);
        return $user;
    }

    public function render()
    {
        $coach = $this->authorizeCoach();
        $enrollments = StudentVehicleCategory::query()->where('status', 'active')
            ->whereHas('student', fn (Builder $q) => $q->where('status', 'active'))
            ->whereHas('coachAssignments', fn (Builder $q) => $q->where('coach_id', $coach->id)->where('status', 'active'))
            ->with(['student', 'vehicleCategory'])
            ->withSum(['trainingSessions as completed_minutes' => fn (Builder $q) => $q->where('status', 'completed')], 'duration_minutes')
            ->get();
        $progress = [];
        foreach ($enrollments as $enrollment) $progress[$enrollment->id] = TrainingProgress::forEnrollment($enrollment);
        $search = mb_strtolower(trim($this->search));
        $filtered = $enrollments->filter(function ($enrollment) use ($search, $progress): bool {
            if ($search !== '' && ! str_contains(mb_strtolower($enrollment->student->student_number.' '.$enrollment->student->full_name), $search)) return false;
            return $this->progressFilter === '' || $progress[$enrollment->id]['status'] === $this->progressFilter;
        })->values();
        $page = max(1, $this->getPage());
        $rows = new \Illuminate\Pagination\LengthAwarePaginator($filtered->slice(($page - 1) * 10, 10)->values(), $filtered->count(), 10, $page, ['path' => request()->url()]);

        return view('livewire.coach.training.index', ['rows' => $rows, 'progress' => $progress])->layout('layouts.coach');
    }
}

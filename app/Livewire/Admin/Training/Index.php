<?php

namespace App\Livewire\Admin\Training;

use App\Models\StudentVehicleCategory;
use App\Models\TrainingCoachAssignment;
use App\Models\User;
use App\Services\TrainingProgress;
use App\Services\TrainingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $coachFilter = '';
    public string $categoryFilter = '';
    public string $progressFilter = '';
    public array $coachSelections = [];

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('Owner') && $user->can('training.view'), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedCoachFilter(): void { $this->resetPage(); }
    public function updatedCategoryFilter(): void { $this->resetPage(); }
    public function updatedProgressFilter(): void { $this->resetPage(); }

    public function assignCoach(int $enrollmentId): void
    {
        $owner = auth()->user();
        abort_unless($owner?->hasRole('Owner') && $owner->can('training.manage'), 403);
        $data = Validator::make(['coachId' => $this->coachSelections[$enrollmentId] ?? null], [
            'coachId' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
        ])->validate();
        $coach = User::role('Coach')->findOrFail($data['coachId']);
        app(TrainingService::class)->assignCoach($enrollmentId, $coach, $owner);
        session()->flash('success', 'Coach assigned to the category enrollment.');
        unset($this->coachSelections[$enrollmentId]);
    }

    public function deactivateAssignment(int $assignmentId): void
    {
        $owner = auth()->user();
        abort_unless($owner?->hasRole('Owner') && $owner->can('training.manage'), 403);
        $assignment = TrainingCoachAssignment::findOrFail($assignmentId);
        app(TrainingService::class)->deactivateAssignment($assignment->getKey());
        session()->flash('success', 'Coach assignment deactivated. Assignment history and sessions were retained.');
    }

    public function render()
    {
        $query = StudentVehicleCategory::query()->with([
            'student', 'vehicleCategory',
            'coachAssignments' => fn ($q) => $q->with('coach'),
        ])->withSum(['trainingSessions as completed_minutes' => fn (Builder $q) => $q->where('status', 'completed')], 'duration_minutes')
            ->where('status', 'active')->whereHas('student', fn (Builder $q) => $q->where('status', 'active'));
        $allEnrollments = (clone $query)->get();
        $requiredCents = $allEnrollments->sum(fn ($enrollment) => (int) round((float) $enrollment->required_training_hours * 100));
        $completedMinutes = $allEnrollments->sum(fn ($enrollment) => (int) ($enrollment->completed_minutes ?? 0));
        $progressById = [];
        foreach ($allEnrollments as $enrollment) $progressById[$enrollment->id] = TrainingProgress::forEnrollment($enrollment);
        $remainingHundredths = $allEnrollments->sum(function ($enrollment) use ($progressById): int {
            return (int) round($progressById[$enrollment->id]['remaining_hours'] * 100);
        });

        $filtered = $allEnrollments->filter(function ($enrollment) use ($progressById): bool {
            $search = mb_strtolower(trim($this->search));
            if ($search !== '' && ! str_contains(mb_strtolower($enrollment->student->student_number.' '.$enrollment->student->full_name), $search)) return false;
            if ($this->categoryFilter !== '' && (string) $enrollment->vehicle_category_id !== $this->categoryFilter) return false;
            if ($this->coachFilter !== '' && ! $enrollment->coachAssignments->contains(fn ($assignment) => $assignment->status === 'active' && (string) $assignment->coach_id === $this->coachFilter)) return false;
            return $this->progressFilter === '' || $progressById[$enrollment->id]['status'] === $this->progressFilter;
        })->values();
        $page = max(1, $this->getPage());
        $items = $filtered->slice(($page - 1) * 10, 10)->values();
        $rows = new \Illuminate\Pagination\LengthAwarePaginator($items, $filtered->count(), 10, $page, [
            'path' => request()->url(), 'pageName' => 'page',
        ]);

        return view('livewire.admin.training.index', [
            'rows' => $rows, 'progressById' => $progressById,
            'totalHours' => number_format($requiredCents / 100, 2),
            'completedHours' => number_format($completedMinutes / 60, 2),
            'remainingHours' => number_format($remainingHundredths / 100, 2),
            'coaches' => User::role('Coach')->where('is_active', true)->orderBy('name')->get(),
            'categories' => \App\Models\VehicleCategory::orderBy('category_code')->get(),
        ])->layout('layouts.admin');
    }
}

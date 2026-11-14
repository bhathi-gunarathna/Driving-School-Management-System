<?php

namespace App\Livewire\Admin\Students;

use App\Models\Student;
use App\Models\VehicleCategory;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $vehicleCategoryFilter = '';

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('students.view_all'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(string $value): void
    {
        if (! in_array($value, ['all', Student::STATUS_ACTIVE, Student::STATUS_INACTIVE], true)) {
            $this->statusFilter = 'all';
        }

        $this->resetPage();
    }

    public function updatedVehicleCategoryFilter(string $value): void
    {
        if ($value !== '' && ! ctype_digit($value)) {
            $this->vehicleCategoryFilter = '';
        }

        $this->resetPage();
    }

    public function toggleStatus(int $studentId): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('students.update'), 403);

        DB::transaction(function () use ($studentId): void {
            $student = Student::query()->lockForUpdate()->findOrFail($studentId);
            $student->status = $student->status === Student::STATUS_ACTIVE
                ? Student::STATUS_INACTIVE
                : Student::STATUS_ACTIVE;
            $student->save();
        });

        session()->flash('success', 'Student status updated.');
    }

    public function render()
    {
        $totalStudents = Student::query()->count();
        $activeStudents = Student::query()->where('status', Student::STATUS_ACTIVE)->count();
        $inactiveStudents = Student::query()->where('status', Student::STATUS_INACTIVE)->count();

        $students = Student::query()
            ->with('vehicleCategories')
            ->when(trim($this->search) !== '', function ($query): void {
                $search = '%'.trim($this->search).'%';
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery->where('student_number', 'like', $search)
                        ->orWhere('full_name', 'like', $search)
                        ->orWhere('nic_number', 'like', $search)
                        ->orWhere('contact_number', 'like', $search);
                });
            })
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->vehicleCategoryFilter !== '', function ($query): void {
                $query->whereHas('vehicleCategories', function ($categoryQuery): void {
                    $categoryQuery
                        ->where('vehicle_categories.id', (int) $this->vehicleCategoryFilter)
                        ->where('student_vehicle_categories.status', 'active');
                });
            })
            ->latest('id')
            ->paginate(10);

        return view('livewire.admin.students.index', [
            'students' => $students,
            'categories' => VehicleCategory::query()->orderBy('category_code')->get(),
            'totalStudents' => $totalStudents,
            'activeStudents' => $activeStudents,
            'inactiveStudents' => $inactiveStudents,
        ])->layout('layouts.admin');
    }
}

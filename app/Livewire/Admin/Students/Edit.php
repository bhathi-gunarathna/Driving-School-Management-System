<?php

namespace App\Livewire\Admin\Students;

use App\Models\Student;
use App\Models\VehicleCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

class Edit extends Component
{
    use WithFileUploads;

    public Student $student;

    public string $full_name = '';

    public string $address = '';

    public string $nic_number = '';

    public string $date_of_birth = '';

    public string $contact_number = '';

    public ?TemporaryUploadedFile $medicalReport = null;

    /** @var array<int, int|string> */
    public array $selectedCategoryIds = [];

    public string $status = Student::STATUS_ACTIVE;

    public function mount(Student $student): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('students.update'), 403);

        $this->student = $student;
        $this->full_name = $student->full_name;
        $this->address = $student->address;
        $this->nic_number = $student->nic_number;
        $this->date_of_birth = $student->date_of_birth->format('Y-m-d');
        $this->contact_number = $student->contact_number;
        $this->status = $student->status;
        $this->selectedCategoryIds = $student->vehicleCategories()
            ->wherePivot('status', 'active')
            ->pluck('vehicle_categories.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:5000'],
            'nic_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('students', 'nic_number')->ignore($this->student->getKey()),
            ],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'contact_number' => ['required', 'string', 'max:25'],
            'medicalReport' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'selectedCategoryIds' => ['present', 'array'],
            'selectedCategoryIds.*' => ['required', 'integer', 'distinct', 'exists:vehicle_categories,id'],
            'status' => ['required', Rule::in([Student::STATUS_ACTIVE, Student::STATUS_INACTIVE])],
        ];
    }

    protected function messages(): array
    {
        return [
            'full_name.required' => 'Enter the student’s full name.',
            'address.required' => 'Enter the student’s address.',
            'nic_number.required' => 'Enter the student’s NIC number.',
            'nic_number.unique' => 'This NIC number is already registered.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'contact_number.required' => 'Enter a contact number.',
            'medicalReport.image' => 'Upload a JPG, JPEG, or PNG image.',
            'medicalReport.mimes' => 'Upload a JPG, JPEG, or PNG image.',
            'medicalReport.max' => 'The medical report image must not exceed 5 MB.',
            'selectedCategoryIds.required' => 'Select at least one vehicle category.',
            'selectedCategoryIds.min' => 'Select at least one vehicle category.',
            'selectedCategoryIds.*.exists' => 'One of the selected categories is no longer available.',
        ];
    }

    public function updateStudent()
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('students.update'), 403);

        $this->full_name = trim($this->full_name);
        $this->address = trim($this->address);
        $this->nic_number = strtoupper(trim($this->nic_number));
        $this->contact_number = trim($this->contact_number);

        $validated = $this->validate();
        $selectedIds = collect($validated['selectedCategoryIds'])->map(fn ($id) => (int) $id)->unique()->values();
        $studentId = $this->student->getKey();
        $medicalReportPath = null;

        if ($this->medicalReport) {
            $storedPath = $this->medicalReport->store('medical-reports', 'local');

            if (! is_string($storedPath)) {
                throw ValidationException::withMessages([
                    'medicalReport' => 'The medical report could not be stored. Please try again.',
                ]);
            }

            $medicalReportPath = $storedPath;
        }

        try {
            DB::transaction(function () use ($validated, $selectedIds, $studentId, $medicalReportPath): void {
                $student = Student::query()->lockForUpdate()->findOrFail($studentId);
                $existingEnrollments = DB::table('student_vehicle_categories')
                    ->where('student_id', $studentId)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('vehicle_category_id');

                $newCategoryIds = $selectedIds->reject(fn ($id) => $existingEnrollments->has($id));
                $activeNewCategories = VehicleCategory::active()
                    ->whereIn('id', $newCategoryIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($activeNewCategories->count() !== $newCategoryIds->count()) {
                    throw ValidationException::withMessages([
                        'selectedCategoryIds' => 'Inactive categories cannot be newly assigned. Refresh the page and choose active categories.',
                    ]);
                }

                $student->full_name = $validated['full_name'];
                $student->address = $validated['address'];
                $student->nic_number = $validated['nic_number'];
                $student->date_of_birth = $validated['date_of_birth'];
                $student->contact_number = $validated['contact_number'];
                $student->status = $validated['status'];

                if ($medicalReportPath !== null) {
                    $student->medical_report_path = $medicalReportPath;
                }

                $student->save();

                foreach ($selectedIds as $categoryId) {
                    if ($existingEnrollments->has($categoryId)) {
                        // Re-activate an old assignment without changing its historical fee/hour snapshot.
                        DB::table('student_vehicle_categories')
                            ->where('student_id', $studentId)
                            ->where('vehicle_category_id', $categoryId)
                            ->update(['status' => 'active', 'updated_at' => now()]);

                        continue;
                    }

                    $category = $activeNewCategories->get($categoryId);
                    $student->vehicleCategories()->attach($categoryId, [
                        'agreed_fee' => $category->price,
                        'required_training_hours' => $category->training_hours,
                        'status' => 'active',
                    ]);
                }

                $removedIds = $existingEnrollments->keys()->diff($selectedIds);

                if ($removedIds->isNotEmpty()) {
                    DB::table('student_vehicle_categories')
                        ->where('student_id', $studentId)
                        ->whereIn('vehicle_category_id', $removedIds)
                        ->update(['status' => 'inactive', 'updated_at' => now()]);
                }

                $this->student = $student->fresh();
            });
        } catch (Throwable $exception) {
            if ($medicalReportPath) {
                Storage::disk('local')->delete($medicalReportPath);
            }

            throw $exception;
        }

        session()->flash('success', 'Student details updated successfully.');

        return $this->redirectRoute('admin.students.view', ['student' => $studentId]);
    }

    public function render()
    {
        $existingEnrollments = $this->student->vehicleCategories()->get()->keyBy('id');
        $selectedIds = collect($this->selectedCategoryIds)->map(fn ($id) => (int) $id);
        $activeEnrollmentIds = $existingEnrollments
            ->filter(fn (VehicleCategory $category) => $category->pivot->status === 'active')
            ->keys();

        $categories = VehicleCategory::query()
            ->where('is_active', true)
            ->orWhereIn('id', $activeEnrollmentIds)
            ->orderBy('category_code')
            ->get();

        $selectedCategories = $selectedIds->map(function (int $categoryId) use ($categories, $existingEnrollments): ?array {
            $category = $categories->firstWhere('id', $categoryId);

            if (! $category) {
                return null;
            }

            $existing = $existingEnrollments->get($categoryId);
            $hasEnrollmentHistory = $existing !== null;

            return [
                'id' => $category->id,
                'code' => $category->category_code,
                'name' => $category->category_name,
                'fee' => $hasEnrollmentHistory ? $existing->pivot->agreed_fee : $category->price,
                'hours' => $hasEnrollmentHistory ? $existing->pivot->required_training_hours : $category->training_hours,
            ];
        })->filter()->values();

        $totalFeeCents = $selectedCategories->sum(fn (array $category): int => (int) round((float) $category['fee'] * 100));
        $totalTrainingHundredths = $selectedCategories->sum(fn (array $category): int => (int) round((float) $category['hours'] * 100));

        return view('livewire.admin.students.edit', [
            'categories' => $categories,
            'existingEnrollments' => $existingEnrollments,
            'selectedCategories' => $selectedCategories,
            'totalCourseFee' => number_format($totalFeeCents / 100, 2),
            'totalTrainingHours' => number_format($totalTrainingHundredths / 100, 2),
        ])->layout('layouts.admin');
    }
}

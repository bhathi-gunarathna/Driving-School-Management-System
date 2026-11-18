<?php

namespace App\Livewire\Admin\Students;

use App\Models\Student;
use App\Models\VehicleCategory;
use App\Models\StudentPaymentPlan;
use App\Models\SystemSetting;
use App\Services\StudentPaymentService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

class Create extends Component
{
    use WithFileUploads;

    public string $full_name = '';

    public string $address = '';

    public string $nic_number = '';

    public string $date_of_birth = '';

    public string $contact_number = '';

    public ?TemporaryUploadedFile $profilePhoto = null;

    public ?TemporaryUploadedFile $medicalReport = null;

    public string $medicalReportReference = '';

    /** @var array<int, int|string> */
    public array $selectedCategoryIds = [];

    public string $paymentPlanType = StudentPaymentPlan::TYPE_FULL;

    public string $advanceAmount = '0.00';

    public string $advancePaymentMethod = 'cash';

    public string $advancePaymentDate = '';

    public string $advanceReference = '';

    public string $advanceNotes = '';

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('students.create'), 403);
        $this->advancePaymentDate = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:5000'],
            'nic_number' => ['required', 'string', 'max:30', Rule::unique('students', 'nic_number')],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'contact_number' => ['required', 'string', 'max:25'],
            'profilePhoto' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'medicalReport' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'medicalReportReference' => ['nullable', 'string', 'max:100'],
            'selectedCategoryIds' => ['required', 'array', 'min:1'],
            'selectedCategoryIds.*' => ['required', 'integer', 'distinct', 'exists:vehicle_categories,id'],
            'paymentPlanType' => ['required', Rule::in([StudentPaymentPlan::TYPE_FULL, StudentPaymentPlan::TYPE_TWO_INSTALLMENTS, StudentPaymentPlan::TYPE_THREE_INSTALLMENTS])],
            'advanceAmount' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999.99'],
            'advancePaymentMethod' => ['required', Rule::in(['cash', 'bank_transfer', 'card', 'other'])],
            'advancePaymentDate' => ['required', 'date', 'before_or_equal:today'],
            'advanceReference' => ['nullable', 'string', 'max:100'],
            'advanceNotes' => ['nullable', 'string', 'max:2000'],
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
            'profilePhoto.image' => 'Upload a JPG, JPEG, or PNG profile photo.',
            'profilePhoto.mimes' => 'Upload a JPG, JPEG, or PNG profile photo.',
            'profilePhoto.max' => 'The profile photo must not exceed 5 MB.',
            'medicalReportReference.max' => 'The medical report reference must not exceed 100 characters.',
            'selectedCategoryIds.required' => 'Select at least one vehicle category.',
            'selectedCategoryIds.min' => 'Select at least one vehicle category.',
            'selectedCategoryIds.*.exists' => 'One of the selected categories is no longer available.',
        ];
    }

    public function registerStudent()
    {
        $user = auth()->user();

        abort_unless($user?->hasRole('Owner') && $user->can('students.create'), 403);

        $this->full_name = trim($this->full_name);
        $this->address = trim($this->address);
        $this->nic_number = strtoupper(trim($this->nic_number));
        $this->contact_number = trim($this->contact_number);
        $this->medicalReportReference = trim($this->medicalReportReference);

        $validated = $this->validate();
        if (Money::toCents($validated['advanceAmount']) > 0) {
            abort_unless($user->can('payments.manage'), 403);
        }
        $selectedIds = collect($validated['selectedCategoryIds'])->map(fn ($id) => (int) $id)->unique()->values();
        $profilePhotoPath = null;
        $medicalReportPath = null;

        try {
            if ($this->profilePhoto) {
                $profilePhotoPath = $this->profilePhoto->store('student-photos', 'local');
                if (! is_string($profilePhotoPath)) {
                    throw ValidationException::withMessages(['profilePhoto' => 'The profile photo could not be stored. Please try again.']);
                }
            }

            if ($this->medicalReport) {
                $medicalReportPath = $this->medicalReport->store('medical-reports', 'local');
                if (! is_string($medicalReportPath)) {
                    throw ValidationException::withMessages(['medicalReport' => 'The medical report could not be stored. Please try again.']);
                }
            }

            $student = DB::transaction(function () use ($validated, $selectedIds, $profilePhotoPath, $medicalReportPath, $user): Student {
                $categories = VehicleCategory::active()
                    ->whereIn('id', $selectedIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($categories->count() !== $selectedIds->count()) {
                    throw ValidationException::withMessages([
                        'selectedCategoryIds' => 'A selected category is inactive or unavailable. Refresh the page and choose active categories.',
                    ]);
                }

                $student = Student::create([
                    'student_number' => 'TEMP-'.Str::uuid(),
                    'full_name' => $validated['full_name'],
                    'address' => $validated['address'],
                    'nic_number' => $validated['nic_number'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'contact_number' => $validated['contact_number'],
                    'profile_photo_path' => $profilePhotoPath,
                    'medical_report_path' => $medicalReportPath,
                    'medical_report_reference' => $validated['medicalReportReference'] ?: null,
                    'status' => Student::STATUS_ACTIVE,
                ]);

                // The auto-increment ID provides a concurrency-safe unique sequence.
                $student->student_number = sprintf('%s-%s-%04d', SystemSetting::current()->student_number_prefix ?: 'DS', now()->format('Y'), $student->getKey());
                $student->save();

                $totalFeeCents = 0;
                foreach ($categories as $category) {
                    $totalFeeCents += Money::toCents((string) $category->price);
                    $student->vehicleCategories()->attach($category->getKey(), [
                        'agreed_fee' => $category->price,
                        'required_training_hours' => $category->training_hours,
                        'status' => 'active',
                    ]);
                }

                $advanceCents = Money::toCents($validated['advanceAmount']);
                if ($advanceCents > $totalFeeCents) {
                    throw ValidationException::withMessages(['advanceAmount' => 'The advance cannot exceed the total course fee.']);
                }

                $plan = app(StudentPaymentService::class)->createPlan($student, $totalFeeCents, $validated['paymentPlanType']);
                if ($advanceCents > 0) {
                    app(StudentPaymentService::class)->recordPayment($plan->getKey(), $user, [
                        'amount' => $validated['advanceAmount'],
                        'payment_date' => $validated['advancePaymentDate'],
                        'payment_method' => $validated['advancePaymentMethod'],
                        'reference_number' => $validated['advanceReference'] ?: null,
                        'notes' => $validated['advanceNotes'] ?: null,
                    ]);
                }

                return $student;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_values(array_filter([$profilePhotoPath, $medicalReportPath])));

            throw $exception;
        }

        session()->flash('success', 'Student registered successfully.');

        return $this->redirectRoute('admin.students.view', ['student' => $student->getKey()]);
    }

    public function render()
    {
        $categories = VehicleCategory::active()->orderBy('category_code')->get();
        $selectedCategories = $categories->whereIn('id', $this->selectedCategoryIds);

        $totalFeeCents = $selectedCategories->sum(fn (VehicleCategory $category): int => Money::toCents((string) $category->price));
        $totalTrainingHundredths = $selectedCategories->sum(fn (VehicleCategory $category): int => (int) round((float) $category->training_hours * 100));

        return view('livewire.admin.students.create', [
            'categories' => $categories,
            'selectedCategories' => $selectedCategories,
            'totalCourseFee' => number_format($totalFeeCents / 100, 2),
            'totalTrainingHours' => number_format($totalTrainingHundredths / 100, 2),
            'installmentAmounts' => Money::splitCents($totalFeeCents, StudentPaymentPlan::installmentCount($this->paymentPlanType)),
        ])->layout('layouts.admin');
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\Students\Create;
use App\Livewire\Admin\Students\Edit;
use App\Livewire\Admin\Students\Index;
use App\Models\Student;
use App\Models\User;
use App\Models\VehicleCategory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Role $ownerRole;

    protected Role $coachRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RolePermissionSeeder::class);

        $this->ownerRole = Role::findByName('Owner', 'web');
        $this->coachRole = Role::findByName('Coach', 'web');
    }

    public function test_owner_can_register_student_with_unique_number_and_enrollment_snapshots(): void
    {
        Storage::fake('local');
        $owner = $this->makeOwner();
        $categoryA = $this->makeCategory(['category_code' => 'A1', 'price' => 10000, 'training_hours' => 12.5]);
        $categoryB = $this->makeCategory(['category_code' => 'B', 'price' => 25000, 'training_hours' => 20]);

        $component = Livewire::actingAs($owner)
            ->test(Create::class)
            ->set('full_name', 'Test Student')
            ->set('address', '12 Main Street')
            ->set('nic_number', '200001011234')
            ->set('date_of_birth', '2000-01-01')
            ->set('contact_number', '0771234567')
            ->set('selectedCategoryIds', [$categoryA->id, $categoryB->id])
            ->set('medicalReport', $this->fakePngUpload())
            ->call('registerStudent');

        $student = Student::query()->where('nic_number', '200001011234')->firstOrFail();

        $component->assertRedirect(route('admin.students.view', $student));
        $this->assertSame(sprintf('DS-%s-%04d', now()->format('Y'), $student->id), $student->student_number);
        $this->assertDatabaseHas('student_vehicle_categories', [
            'student_id' => $student->id,
            'vehicle_category_id' => $categoryA->id,
            'agreed_fee' => '10000.00',
            'required_training_hours' => '12.50',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('student_vehicle_categories', [
            'student_id' => $student->id,
            'vehicle_category_id' => $categoryB->id,
            'agreed_fee' => '25000.00',
            'required_training_hours' => '20.00',
            'status' => 'active',
        ]);
        $this->assertNotNull($student->medical_report_path);
        Storage::disk('local')->assertExists($student->medical_report_path);
    }

    public function test_registration_rejects_duplicate_nic(): void
    {
        $owner = $this->makeOwner();
        $activeCategory = $this->makeCategory();
        $this->makeStudent(['nic_number' => '200001011234']);

        Livewire::actingAs($owner)
            ->test(Create::class)
            ->set('full_name', 'Another Student')
            ->set('address', 'Address')
            ->set('nic_number', '200001011234')
            ->set('date_of_birth', '2000-01-01')
            ->set('contact_number', '0771111111')
            ->set('selectedCategoryIds', [$activeCategory->id])
            ->call('registerStudent')
            ->assertHasErrors(['nic_number' => 'unique']);
    }

    public function test_registration_rejects_an_inactive_category(): void
    {
        $owner = $this->makeOwner();
        $inactiveCategory = $this->makeCategory(['is_active' => false]);

        Livewire::actingAs($owner)
            ->test(Create::class)
            ->set('full_name', 'Another Student')
            ->set('address', 'Address')
            ->set('nic_number', '200001011234')
            ->set('date_of_birth', '2000-01-01')
            ->set('contact_number', '0771111111')
            ->set('selectedCategoryIds', [$inactiveCategory->id])
            ->call('registerStudent')
            ->assertHasErrors(['selectedCategoryIds']);
    }

    public function test_medical_report_rejects_non_image_uploads(): void
    {
        $owner = $this->makeOwner();
        $category = $this->makeCategory();

        Livewire::actingAs($owner)
            ->test(Create::class)
            ->set('full_name', 'Another Student')
            ->set('address', 'Address')
            ->set('nic_number', '200001011234')
            ->set('date_of_birth', '2000-01-01')
            ->set('contact_number', '0771111111')
            ->set('selectedCategoryIds', [$category->id])
            ->set('medicalReport', UploadedFile::fake()->create('report.pdf', 20, 'application/pdf'))
            ->call('registerStudent')
            ->assertHasErrors(['medicalReport']);
    }

    public function test_medical_report_enforces_the_five_megabyte_size_limit(): void
    {
        $owner = $this->makeOwner();
        $category = $this->makeCategory();
        $oversizedImage = $this->fakePngUpload()->size(5121);

        Livewire::actingAs($owner)
            ->test(Create::class)
            ->set('full_name', 'Another Student')
            ->set('address', 'Address')
            ->set('nic_number', '200001011234')
            ->set('date_of_birth', '2000-01-01')
            ->set('contact_number', '0771111111')
            ->set('selectedCategoryIds', [$category->id])
            ->set('medicalReport', $oversizedImage)
            ->call('registerStudent')
            ->assertHasErrors(['medicalReport' => 'max']);
    }

    public function test_registration_summary_calculates_fee_and_training_totals(): void
    {
        $owner = $this->makeOwner();
        $categoryA = $this->makeCategory(['category_code' => 'A1', 'price' => 10000, 'training_hours' => 12.5]);
        $categoryB = $this->makeCategory(['category_code' => 'B', 'price' => 25000, 'training_hours' => 20]);

        Livewire::actingAs($owner)
            ->test(Create::class)
            ->set('selectedCategoryIds', [$categoryA->id, $categoryB->id])
            ->assertSee('35,000.00')
            ->assertSee('32.50 hours');
    }

    public function test_edit_adds_and_removes_categories_without_changing_historical_snapshots(): void
    {
        $owner = $this->makeOwner();
        $originalCategory = $this->makeCategory(['category_code' => 'A1', 'price' => 10000, 'training_hours' => 12]);
        $newCategory = $this->makeCategory(['category_code' => 'B', 'price' => 25000, 'training_hours' => 20]);
        $student = $this->makeStudent();
        $student->vehicleCategories()->attach($originalCategory->id, [
            'agreed_fee' => '9000.00',
            'required_training_hours' => '10.00',
            'status' => 'active',
        ]);

        $originalCategory->update(['price' => 50000, 'training_hours' => 30]);

        Livewire::actingAs($owner)
            ->test(Edit::class, ['student' => $student])
            ->set('selectedCategoryIds', [$originalCategory->id, $newCategory->id])
            ->call('updateStudent');

        $this->assertDatabaseHas('student_vehicle_categories', [
            'student_id' => $student->id,
            'vehicle_category_id' => $originalCategory->id,
            'agreed_fee' => '9000.00',
            'required_training_hours' => '10.00',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('student_vehicle_categories', [
            'student_id' => $student->id,
            'vehicle_category_id' => $newCategory->id,
            'agreed_fee' => '25000.00',
            'required_training_hours' => '20.00',
            'status' => 'active',
        ]);

        Livewire::actingAs($owner)
            ->test(Edit::class, ['student' => $student->fresh()])
            ->set('selectedCategoryIds', [$originalCategory->id])
            ->call('updateStudent');

        $this->assertDatabaseHas('student_vehicle_categories', [
            'student_id' => $student->id,
            'vehicle_category_id' => $newCategory->id,
            'agreed_fee' => '25000.00',
            'required_training_hours' => '20.00',
            'status' => 'inactive',
        ]);
    }

    public function test_edit_rejects_duplicate_nic(): void
    {
        $owner = $this->makeOwner();
        $student = $this->makeStudent();
        $otherStudent = $this->makeStudent();

        Livewire::actingAs($owner)
            ->test(Edit::class, ['student' => $student])
            ->set('nic_number', $otherStudent->nic_number)
            ->call('updateStudent')
            ->assertHasErrors(['nic_number' => 'unique']);
    }

    public function test_edit_rejects_new_inactive_category_assignment(): void
    {
        $owner = $this->makeOwner();
        $student = $this->makeStudent();
        $inactiveCategory = $this->makeCategory(['is_active' => false]);

        Livewire::actingAs($owner)
            ->test(Edit::class, ['student' => $student])
            ->set('selectedCategoryIds', [$inactiveCategory->id])
            ->call('updateStudent')
            ->assertHasErrors(['selectedCategoryIds']);
    }

    public function test_student_list_search_category_filter_and_pagination(): void
    {
        $owner = $this->makeOwner();
        $category = $this->makeCategory(['category_code' => 'CAR']);
        $otherCategory = $this->makeCategory(['category_code' => 'BIKE']);

        $searchStudent = $this->makeStudent(['student_number' => 'DS-2026-SEARCH', 'full_name' => 'Search Target']);
        $searchStudent->vehicleCategories()->attach($category->id, ['agreed_fee' => 100, 'required_training_hours' => 5, 'status' => 'active']);
        $otherStudent = $this->makeStudent(['student_number' => 'DS-2026-OTHER', 'full_name' => 'Other Target']);
        $otherStudent->vehicleCategories()->attach($otherCategory->id, ['agreed_fee' => 100, 'required_training_hours' => 5, 'status' => 'active']);

        $component = Livewire::actingAs($owner)->test(Index::class)
            ->set('search', 'SEARCH')
            ->assertSee('Search Target')
            ->assertDontSee('Other Target');

        $component->set('search', '')
            ->set('vehicleCategoryFilter', (string) $category->id)
            ->assertSee('Search Target')
            ->assertDontSee('Other Target');

        $inactiveStudent = $this->makeStudent([
            'student_number' => 'DS-2026-INACTIVE',
            'full_name' => 'Inactive Target',
            'status' => Student::STATUS_INACTIVE,
        ]);
        $inactiveStudent->vehicleCategories()->attach($category->id, ['agreed_fee' => 100, 'required_training_hours' => 5, 'status' => 'active']);

        $component->set('vehicleCategoryFilter', '')
            ->set('statusFilter', 'inactive')
            ->assertSee('Inactive Target')
            ->assertDontSee('Search Target')
            ->assertSee('Inactive Students');

        for ($number = 1; $number <= 11; $number++) {
            $this->makeStudent(['student_number' => sprintf('DS-2026-P%02d', $number)]);
        }

        Livewire::actingAs($owner)->test(Index::class)
            ->call('gotoPage', 2)
            ->assertSee('DS-2026-P01')
            ->assertDontSee('DS-2026-P11');
    }

    public function test_student_profile_and_private_medical_report_require_owner_permissions(): void
    {
        Storage::fake('local');
        $owner = $this->makeOwner();
        $student = $this->makeStudent();
        $category = $this->makeCategory(['category_code' => 'PROFILE', 'price' => 12500, 'training_hours' => 7.5]);
        $student->vehicleCategories()->attach($category->id, [
            'agreed_fee' => '12000.00',
            'required_training_hours' => '7.00',
            'status' => 'active',
        ]);
        $student->medical_report_path = $this->fakePngUpload()->store('medical-reports', 'local');
        $student->save();

        $this->actingAs($owner)
            ->get(route('admin.students.view', $student))
            ->assertOk()
            ->assertSee($student->student_number)
            ->assertSee($student->full_name)
            ->assertSee($student->nic_number)
            ->assertSee($student->contact_number)
            ->assertSee($category->category_code)
            ->assertSee('12,000.00')
            ->assertSee('7.00 hrs')
            ->assertSee('Medical Report')
            ->assertSee(route('admin.students.medical-report', $student));

        $this->actingAs($owner)
            ->get(route('admin.students.medical-report', $student))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $coach = User::factory()->create();
        $coach->assignRole($this->coachRole);

        $this->actingAs($coach)
            ->get(route('admin.students.medical-report', $student))
            ->assertForbidden();
    }

    public function test_owner_can_change_student_status_without_deleting_record(): void
    {
        $owner = $this->makeOwner();
        $student = $this->makeStudent();

        Livewire::actingAs($owner)->test(Index::class)->call('toggleStatus', $student->id);

        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'inactive']);
        $this->assertSame(1, Student::query()->count());
    }

    public function test_coach_cannot_access_student_management_pages(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole($this->coachRole);

        $this->actingAs($coach)->get(route('admin.students.index'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.students.create'))->assertForbidden();
    }

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole($this->ownerRole);

        return $owner;
    }

    private function fakePngUpload(): File
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/+S8AAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent('medical.png', $png ?: '');
    }

    /** @param array<string, mixed> $attributes */
    private function makeCategory(array $attributes = []): VehicleCategory
    {
        return VehicleCategory::create(array_merge([
            'category_code' => 'CAT'.Str::upper(Str::random(5)),
            'category_name' => 'Test Category',
            'description' => null,
            'training_hours' => 10,
            'price' => 1000,
            'is_active' => true,
        ], $attributes));
    }

    /** @param array<string, mixed> $attributes */
    private function makeStudent(array $attributes = []): Student
    {
        return Student::create(array_merge([
            'student_number' => 'TEST-'.Str::upper(Str::random(20)),
            'full_name' => 'Test Student',
            'address' => 'Test address',
            'nic_number' => 'NIC-'.Str::upper(Str::random(16)),
            'date_of_birth' => '2000-01-01',
            'contact_number' => '0770000000',
            'medical_report_path' => null,
            'status' => Student::STATUS_ACTIVE,
        ], $attributes));
    }
}

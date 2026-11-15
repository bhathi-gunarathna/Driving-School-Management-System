<?php

namespace Tests\Feature;

use App\Livewire\Admin\Students\View as StudentView;
use App\Livewire\Admin\Training\Index as TrainingIndex;
use App\Livewire\Coach\Training\Dashboard;
use App\Livewire\Coach\Training\Index as CoachTrainingIndex;
use App\Livewire\Coach\Training\Record;
use App\Models\Student;
use App\Models\StudentVehicleCategory;
use App\Models\TrainingCoachAssignment;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\VehicleCategory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingManagementTest extends TestCase
{
    use RefreshDatabase;

    private Role $ownerRole;
    private Role $coachRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->ownerRole = Role::findByName('Owner', 'web');
        $this->coachRole = Role::findByName('Coach', 'web');
    }

    public function test_owner_can_assign_multiple_coaches_by_category_and_deactivate_assignment(): void
    {
        $owner = $this->owner();
        $student = $this->student();
        $enrollmentA = $this->enrollment($student, 'A');
        $enrollmentB = $this->enrollment($student, 'B');
        $coachA = $this->coach();
        $coachB = $this->coach();

        $component = Livewire::actingAs($owner)->test(TrainingIndex::class)
            ->set("coachSelections.{$enrollmentA->id}", $coachA->id)->call('assignCoach', $enrollmentA->id)
            ->set("coachSelections.{$enrollmentA->id}", $coachB->id)->call('assignCoach', $enrollmentA->id)
            ->set("coachSelections.{$enrollmentB->id}", $coachA->id)->call('assignCoach', $enrollmentB->id);

        $this->assertDatabaseCount('training_coach_assignments', 3);
        $assignment = TrainingCoachAssignment::where('student_vehicle_category_id', $enrollmentA->id)->where('coach_id', $coachA->id)->firstOrFail();
        $component->call('deactivateAssignment', $assignment->id);
        $this->assertDatabaseHas('training_coach_assignments', ['id' => $assignment->id, 'status' => 'inactive']);
        $this->assertDatabaseCount('training_coach_assignments', 3);
    }

    public function test_coach_records_minutes_and_excess_hours_are_preserved_and_shown(): void
    {
        $owner = $this->owner();
        $coach = $this->coach();
        $student = $this->student();
        $enrollment = $this->enrollment($student, 'B', '1.00');
        $assignment = $this->assign($enrollment, $coach, $owner);

        Livewire::actingAs($coach)->test(Record::class, ['enrollment' => $enrollment->id])
            ->set('training_date', now()->subDay()->toDateString())->set('start_time', '09:00')->set('end_time', '10:30')
            ->call('record')->assertRedirect(route('coach.training.index'));

        $this->assertDatabaseHas('training_sessions', [
            'student_vehicle_category_id' => $enrollment->id,
            'coach_id' => $coach->id,
            'coach_assignment_id' => $assignment->id,
            'duration_minutes' => 90,
            'status' => 'completed',
        ]);
        $this->actingAs($owner)->get(route('admin.students.view', $student))->assertOk()->assertSee('Over Hours')->assertSee('1.50 hrs');
    }

    public function test_recording_validates_future_dates_duration_and_active_assignment(): void
    {
        $owner = $this->owner();
        $coach = $this->coach();
        $student = $this->student();
        $enrollment = $this->enrollment($student);
        $this->assign($enrollment, $coach, $owner);

        Livewire::actingAs($coach)->test(Record::class, ['enrollment' => $enrollment->id])
            ->set('training_date', now()->addDay()->toDateString())->set('duration_minutes', '30')->call('record')
            ->assertHasErrors(['training_date']);

        Livewire::actingAs($coach)->test(Record::class, ['enrollment' => $enrollment->id])
            ->set('training_date', now()->subDay()->toDateString())->set('start_time', '12:00')->set('end_time', '11:00')
            ->call('record')->assertHasErrors(['end_time']);

        $this->assertDatabaseCount('training_sessions', 0);
        app(\App\Services\TrainingService::class)->deactivateAssignment($this->assignment($enrollment, $coach)->id);
        $this->get(route('coach.training.record', $enrollment->id))->assertNotFound();
    }

    public function test_coach_can_only_see_assigned_active_students_and_inactive_coaches_are_blocked(): void
    {
        $owner = $this->owner();
        $coach = $this->coach();
        $otherCoach = $this->coach();
        $assignedStudent = $this->student(['full_name' => 'Assigned Student']);
        $unassignedStudent = $this->student(['full_name' => 'Secret Student']);
        $enrollment = $this->enrollment($assignedStudent);
        $this->enrollment($unassignedStudent);
        $this->assign($enrollment, $coach, $owner);

        Livewire::actingAs($coach)->test(CoachTrainingIndex::class)->assertSee('Assigned Student')->assertDontSee('Secret Student');
        Livewire::actingAs($otherCoach)->test(Record::class, ['enrollment' => $enrollment->id])->assertStatus(404);
        $this->actingAs($coach)->get(route('admin.payments.index'))->assertForbidden();

        $coach->update(['is_active' => false]);
        $this->actingAs($coach)->get(route('coach.dashboard'))->assertForbidden();
    }

    public function test_owner_can_view_training_dashboard_and_student_profile_shows_assignments_and_history(): void
    {
        $owner = $this->owner();
        $coach = $this->coach();
        $student = $this->student();
        $enrollment = $this->enrollment($student, 'M', '2.00');
        $assignment = $this->assign($enrollment, $coach, $owner);
        TrainingSession::create([
            'student_vehicle_category_id' => $enrollment->id, 'coach_id' => $coach->id,
            'coach_assignment_id' => $assignment->id, 'training_date' => now()->subDays(2)->toDateString(),
            'duration_minutes' => 60, 'status' => 'completed', 'notes' => 'Road practice',
        ]);

        $this->actingAs($owner)->get(route('admin.training.index'))->assertOk()->assertSee($student->student_number);
        $this->actingAs($owner)->get(route('admin.students.view', $student))->assertOk()->assertSee($coach->name)->assertSee('Road practice')->assertSee('1.00 hrs');
        Livewire::actingAs($coach)->test(Dashboard::class)->assertSee('Assigned students');
    }

    public function test_inactive_student_or_enrollment_cannot_receive_training(): void
    {
        $owner = $this->owner();
        $coach = $this->coach();
        $student = $this->student(['status' => 'inactive']);
        $enrollment = $this->enrollment($student);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(\App\Services\TrainingService::class)->assignCoach($enrollment->id, $coach, $owner);
    }

    private function owner(): User
    {
        $user = User::factory()->create(); $user->assignRole($this->ownerRole); return $user;
    }

    private function coach(): User
    {
        $user = User::factory()->create(['is_active' => true]); $user->assignRole($this->coachRole); return $user;
    }

    private function student(array $overrides = []): Student
    {
        return Student::create(array_merge(['student_number' => 'DS-'.Str::upper(Str::random(10)), 'full_name' => 'Training Student', 'address' => 'Address', 'nic_number' => 'NIC-'.Str::upper(Str::random(12)), 'date_of_birth' => '2000-01-01', 'contact_number' => '0771234567', 'status' => 'active'], $overrides));
    }

    private function enrollment(Student $student, string $code = 'CAT', string $hours = '2.00'): StudentVehicleCategory
    {
        $category = VehicleCategory::create(['category_code' => $code.Str::upper(Str::random(4)), 'category_name' => 'Training Category', 'training_hours' => $hours, 'price' => 1000, 'is_active' => true]);
        $student->vehicleCategories()->attach($category->id, ['agreed_fee' => '1000.00', 'required_training_hours' => $hours, 'status' => 'active']);
        return StudentVehicleCategory::where('student_id', $student->id)->where('vehicle_category_id', $category->id)->firstOrFail();
    }

    private function assign(StudentVehicleCategory $enrollment, User $coach, User $owner): TrainingCoachAssignment
    {
        return TrainingCoachAssignment::create(['student_vehicle_category_id' => $enrollment->id, 'coach_id' => $coach->id, 'assigned_by' => $owner->id, 'status' => 'active', 'assigned_at' => now()]);
    }

    private function assignment(StudentVehicleCategory $enrollment, User $coach): TrainingCoachAssignment
    {
        return TrainingCoachAssignment::where('student_vehicle_category_id', $enrollment->id)->where('coach_id', $coach->id)->firstOrFail();
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\Trials\Create as CreateTrial;
use App\Livewire\Admin\Trials\Index as TrialIndex;
use App\Livewire\Admin\Trials\View as TrialView;
use App\Livewire\Admin\Students\View as StudentView;
use App\Livewire\Coach\Trials\Index as CoachTrialIndex;
use App\Livewire\Coach\Trials\View as CoachTrialView;
use App\Models\Student;
use App\Models\StudentVehicleCategory;
use App\Models\TrainingCoachAssignment;
use App\Models\Trial;
use App\Models\TrialCategoryResult;
use App\Models\TrialParticipant;
use App\Models\User;
use App\Models\VehicleCategory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrialManagementTest extends TestCase
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

    public function test_owner_creates_trial_and_number_is_generated(): void
    {
        $owner = $this->owner();
        $component = Livewire::actingAs($owner)->test(CreateTrial::class)
            ->set('trial_date', now()->addDays(2)->toDateString())
            ->set('reporting_time', '08:30')->set('location', 'Test Grounds')->call('createTrial');
        $trial = Trial::firstOrFail();
        $component->assertRedirect(route('admin.trials.view', $trial));
        $this->assertSame(sprintf('DS-TRIAL-%s-%06d', now()->format('Y'), $trial->id), $trial->trial_number);
        $this->assertSame(Trial::STATUS_SCHEDULED, $trial->status);

        Livewire::actingAs($owner)->test(CreateTrial::class)
            ->set('trial_date', now()->subDay()->toDateString())->call('createTrial')->assertHasErrors(['trial_date']);
    }

    public function test_owner_assigns_multiple_students_and_categories_and_duplicate_category_is_rejected(): void
    {
        $owner = $this->owner();
        $trial = $this->trial($owner);
        $studentA = $this->student();
        $catA = $this->enrollment($studentA, 'A');
        $catB = $this->enrollment($studentA, 'B');
        $studentB = $this->student();
        $catC = $this->enrollment($studentB, 'C');

        $component = Livewire::actingAs($owner)->test(TrialView::class, ['trial' => $trial])
            ->set('studentId', (string) $studentA->id)->set('enrollmentIds', [(string) $catA->id, (string) $catB->id])->call('addParticipant')
            ->set('studentId', (string) $studentB->id)->set('enrollmentIds', [(string) $catC->id])->call('addParticipant');
        $this->assertDatabaseCount('trial_participants', 2);
        $this->assertDatabaseCount('trial_category_results', 3);
        $this->assertDatabaseHas('trial_category_results', ['student_vehicle_category_id' => $catA->id, 'result' => 'pending']);

        $component->set('studentId', (string) $studentA->id)->set('enrollmentIds', [(string) $catA->id])->call('addParticipant')->assertHasErrors(['enrollmentIds']);
        $this->assertDatabaseCount('trial_participants', 2);
        $this->assertDatabaseCount('trial_category_results', 3);
    }

    public function test_inactive_students_and_enrollments_cannot_be_assigned_but_training_warning_does_not_block(): void
    {
        $owner = $this->owner();
        $trial = $this->trial($owner);
        $student = $this->student();
        $enrollment = $this->enrollment($student, 'LOW', '5.00');
        $component = Livewire::actingAs($owner)->test(TrialView::class, ['trial' => $trial])
            ->set('studentId', (string) $student->id)->assertSee('Training remaining; Owner may proceed with assignment.')
            ->set('enrollmentIds', [(string) $enrollment->id])->call('addParticipant')->assertHasNoErrors();

        $inactiveStudent = $this->student(['status' => 'inactive']);
        $inactiveEnrollment = $this->enrollment($inactiveStudent);
        $component->set('studentId', (string) $inactiveStudent->id)->set('enrollmentIds', [(string) $inactiveEnrollment->id])
            ->call('addParticipant')->assertHasErrors(['studentId']);
        $activeStudent = $this->student();
        $inactiveCategoryEnrollment = $this->enrollment($activeStudent, 'OLD');
        $inactiveCategoryEnrollment->update(['status' => 'inactive']);
        $component->set('studentId', (string) $activeStudent->id)->set('enrollmentIds', [(string) $inactiveCategoryEnrollment->id])
            ->call('addParticipant')->assertHasErrors(['enrollmentIds']);
        $this->assertDatabaseCount('trial_participants', 1);
    }

    public function test_pass_and_fail_results_are_per_category_audited_and_retrial_keeps_previous_attempt(): void
    {
        $owner = $this->owner();
        $student = $this->student();
        $passedCategory = $this->enrollment($student, 'PASS');
        $failedCategory = $this->enrollment($student, 'FAIL');
        $firstTrial = $this->trial($owner);
        $participant = $this->assign($firstTrial, $student, [$passedCategory, $failedCategory]);
        $firstTrial->update(['trial_date' => now()->subDay()->toDateString()]);
        $firstTrial->refresh();
        $passResult = $participant->categoryResults()->where('student_vehicle_category_id', $passedCategory->id)->firstOrFail();
        $failResult = $participant->categoryResults()->where('student_vehicle_category_id', $failedCategory->id)->firstOrFail();

        Livewire::actingAs($owner)->test(TrialView::class, ['trial' => $firstTrial])
            ->set("resultNotes.{$passResult->id}", 'Clear pass')->call('recordResult', $passResult->id, 'passed')
            ->set("resultNotes.{$failResult->id}", 'Needs another attempt')->call('recordResult', $failResult->id, 'failed');
        $this->assertDatabaseHas('trial_category_results', ['id' => $passResult->id, 'result' => 'passed']);
        $this->assertDatabaseHas('trial_category_results', ['id' => $failResult->id, 'result' => 'failed']);
        $this->assertDatabaseCount('trial_result_histories', 2);

        $retry = $this->trial($owner);
        $retryParticipant = $this->assign($retry, $student, [$failedCategory]);
        $retryResult = $retryParticipant->categoryResults()->firstOrFail();
        $this->assertSame('pending', $retryResult->result);
        $this->assertDatabaseHas('trial_category_results', ['id' => $passResult->id, 'result' => 'passed']);
        $this->assertDatabaseHas('trial_category_results', ['id' => $failResult->id, 'result' => 'failed']);

        Livewire::actingAs($owner)->test(TrialView::class, ['trial' => $firstTrial])
            ->call('recordResult', $failResult->id, 'passed');
        $this->assertDatabaseHas('trial_result_histories', ['trial_category_result_id' => $failResult->id, 'previous_result' => 'failed', 'new_result' => 'passed']);

        Livewire::actingAs($owner)->test(StudentView::class, ['student' => $student])
            ->assertSee('Trial History')->assertSee($firstTrial->trial_number)->assertSee($retry->trial_number)->assertSee('Pending');
    }

    public function test_coach_sees_only_trials_for_active_category_assignments_and_can_record_relevant_result(): void
    {
        $owner = $this->owner();
        $coach = $this->coach();
        $otherCoach = $this->coach();
        $studentA = $this->student(['full_name' => 'Assigned to Coach']);
        $enrollmentA = $this->enrollment($studentA, 'REL');
        $studentB = $this->student(['full_name' => 'Not Assigned to Coach']);
        $enrollmentB = $this->enrollment($studentB, 'HIDE');
        $trial = $this->trial($owner);
        $participantA = $this->assign($trial, $studentA, [$enrollmentA]);
        $participantB = $this->assign($trial, $studentB, [$enrollmentB]);
        $trial->update(['trial_date' => now()->subDay()->toDateString()]);
        $trial->refresh();
        TrainingCoachAssignment::create(['student_vehicle_category_id' => $enrollmentA->id, 'coach_id' => $coach->id, 'assigned_by' => $owner->id, 'status' => 'active', 'assigned_at' => now()]);
        TrainingCoachAssignment::create(['student_vehicle_category_id' => $enrollmentB->id, 'coach_id' => $otherCoach->id, 'assigned_by' => $owner->id, 'status' => 'active', 'assigned_at' => now()]);
        $this->assertSame([$enrollmentA->id], TrainingCoachAssignment::where('coach_id', $coach->id)->where('status', 'active')->pluck('student_vehicle_category_id')->all());
        $resultA = $participantA->categoryResults()->firstOrFail();
        $resultB = $participantB->categoryResults()->firstOrFail();

        Livewire::actingAs($coach)->test(CoachTrialIndex::class)->assertSee('Assigned to Coach')->assertDontSee('Not Assigned to Coach');
        Livewire::actingAs($coach)->test(CoachTrialView::class, ['trial' => $trial])
            ->assertSee('Assigned to Coach')->assertDontSee('Not Assigned to Coach')->call('recordResult', $resultA->id, 'passed');
        $this->assertDatabaseHas('trial_category_results', ['id' => $resultA->id, 'result' => 'passed']);

        Livewire::actingAs($coach)->test(CoachTrialView::class, ['trial' => $trial])
            ->call('recordResult', $resultB->id, 'failed')->assertNotFound();
        $this->assertDatabaseHas('trial_category_results', ['id' => $resultB->id, 'result' => 'pending']);

        $coach->update(['is_active' => false]);
        $this->actingAs($coach)->get(route('coach.trials.index'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.trials.index'))->assertForbidden();
    }

    public function test_trial_list_filters_and_participant_page_is_print_friendly(): void
    {
        $owner = $this->owner();
        $trial = $this->trial($owner);
        $student = $this->student();
        $enrollment = $this->enrollment($student, 'CAR');
        $this->assign($trial, $student, [$enrollment]);

        Livewire::actingAs($owner)->test(TrialIndex::class)->set('search', $trial->trial_number)->assertSee($trial->trial_number)
            ->assertDontSee('DS-TRIAL-NOT-FOUND');
        $this->actingAs($owner)->get(route('admin.trials.view', $trial))->assertOk()->assertSee($student->student_number)->assertSee('Print participant list / Save PDF')->assertSee('print:hidden');
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
        return Student::create(array_merge(['student_number' => 'DS-'.Str::upper(Str::random(10)), 'full_name' => 'Trial Student', 'address' => 'Address', 'nic_number' => 'NIC-'.Str::upper(Str::random(12)), 'date_of_birth' => '2000-01-01', 'contact_number' => '0771122334', 'status' => 'active'], $overrides));
    }

    private function enrollment(Student $student, string $code = 'CAT', string $hours = '2.00'): StudentVehicleCategory
    {
        $category = VehicleCategory::create(['category_code' => $code.Str::upper(Str::random(4)), 'category_name' => 'Trial '.$code, 'training_hours' => $hours, 'price' => '5000.00', 'is_active' => true]);
        $student->vehicleCategories()->attach($category->id, ['agreed_fee' => '5000.00', 'required_training_hours' => $hours, 'status' => 'active']);
        return StudentVehicleCategory::where('student_id', $student->id)->where('vehicle_category_id', $category->id)->firstOrFail();
    }

    private function trial(User $owner, ?string $date = null): Trial
    {
        return Trial::create(['trial_number' => 'DS-TRIAL-'.Str::upper(Str::random(10)), 'trial_date' => $date ?? now()->addDays(3)->toDateString(), 'location' => 'Trial Ground', 'status' => 'scheduled', 'created_by' => $owner->id]);
    }

    /** @param list<StudentVehicleCategory> $enrollments */
    private function assign(Trial $trial, Student $student, array $enrollments): TrialParticipant
    {
        return app(\App\Services\TrialService::class)->assignStudent($trial, $student, array_map(fn ($enrollment) => $enrollment->id, $enrollments));
    }
}

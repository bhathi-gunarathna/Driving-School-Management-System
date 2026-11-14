<?php

namespace Tests\Feature;

use App\Livewire\Admin\Payments\Create;
use App\Livewire\Admin\Payments\Index;
use App\Livewire\Admin\Payments\Receipt;
use App\Livewire\Admin\Students\Create as RegisterStudent;
use App\Livewire\Admin\Students\View as ViewStudent;
use App\Models\Student;
use App\Models\StudentPaymentPlan;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Services\StudentPaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected Role $ownerRole;
    protected Role $coachRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->ownerRole = Role::findByName('Owner', 'web');
        $this->coachRole = Role::findByName('Coach', 'web');
    }

    public function test_registration_creates_snapshot_plan_installments_and_optional_advance_receipt(): void
    {
        $owner = $this->owner();
        $category = $this->category(['price' => '100.01']);
        $component = Livewire::actingAs($owner)->test(RegisterStudent::class)
            ->set('full_name', 'Student One')->set('address', 'Address')->set('nic_number', 'NIC-ONE')
            ->set('date_of_birth', '2000-01-01')->set('contact_number', '0771234567')
            ->set('selectedCategoryIds', [$category->id])->set('paymentPlanType', StudentPaymentPlan::TYPE_THREE_INSTALLMENTS)
            ->set('advanceAmount', '20.00')->call('registerStudent');

        $student = Student::where('nic_number', 'NIC-ONE')->firstOrFail();
        $this->assertDatabaseHas('student_payment_plans', ['student_id' => $student->id, 'total_agreed_fee' => '100.01', 'total_amount_paid' => '20.00', 'outstanding_balance' => '80.01', 'payment_status' => 'partially_paid']);
        $this->assertSame(['33.34', '33.34', '33.33'], $student->paymentPlan->installments->pluck('planned_amount')->all());
        $this->assertDatabaseCount('student_payments', 1);
        $this->assertDatabaseHas('student_payments', ['student_id' => $student->id, 'amount' => '20.00']);
        $component->assertRedirect(route('admin.students.view', $student));

        Livewire::actingAs($owner)->test(RegisterStudent::class)
            ->set('full_name', 'Student Two')->set('address', 'Address')->set('nic_number', 'NIC-TWO')
            ->set('date_of_birth', '2000-01-01')->set('contact_number', '0771234567')
            ->set('selectedCategoryIds', [$category->id])->set('advanceAmount', '0.00')->call('registerStudent');
        $this->assertDatabaseCount('student_payments', 1);
    }

    public function test_additional_payment_updates_balances_and_receipt_is_viewable(): void
    {
        $owner = $this->owner();
        $student = $this->student();
        $plan = app(StudentPaymentService::class)->createPlan($student, 10000, StudentPaymentPlan::TYPE_TWO_INSTALLMENTS);

        $component = Livewire::actingAs($owner)->test(Create::class, ['student' => $student])
            ->set('amount', '30.00')->set('payment_method', 'bank_transfer')->set('reference_number', 'TR-1')
            ->call('record');
        $payment = $student->payments()->firstOrFail();
        $component->assertRedirect(route('admin.payments.receipt', $payment));
        $this->assertSame('70.00', $plan->fresh()->outstanding_balance);
        $this->assertSame('partially_paid', $plan->fresh()->payment_status);

        Livewire::actingAs($owner)->test(Receipt::class, ['payment' => $payment])
            ->assertSee($payment->receipt_number)->assertSee('30.00')->assertSee('70.00');
        Livewire::actingAs($owner)->test(Index::class)->assertSee($student->student_number)->assertSee($payment->receipt_number);
    }

    public function test_payment_cannot_exceed_balance_and_coach_cannot_manage_payments(): void
    {
        $owner = $this->owner();
        $student = $this->student();
        app(StudentPaymentService::class)->createPlan($student, 5000, StudentPaymentPlan::TYPE_FULL);

        Livewire::actingAs($owner)->test(Create::class, ['student' => $student])
            ->set('amount', '50.01')->call('record')->assertHasErrors(['amount']);
        $coach = User::factory()->create();
        $coach->assignRole($this->coachRole);
        $this->actingAs($coach)->get(route('admin.payments.index'))->assertForbidden();
        $this->assertDatabaseCount('student_payments', 0);
    }

    public function test_owner_can_create_plan_for_existing_student_from_saved_enrollment_fees(): void
    {
        $owner = $this->owner();
        $student = $this->student();
        $category = $this->category(['price' => '500.55']);
        $student->vehicleCategories()->attach($category->id, [
            'agreed_fee' => '450.25', 'required_training_hours' => 8, 'status' => 'active',
        ]);

        Livewire::actingAs($owner)->test(ViewStudent::class, ['student' => $student])
            ->set('newPaymentPlanType', StudentPaymentPlan::TYPE_TWO_INSTALLMENTS)
            ->call('createPaymentPlan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_payment_plans', [
            'student_id' => $student->id,
            'total_agreed_fee' => '450.25',
            'number_of_installments' => 2,
        ]);
        $this->assertSame(['225.13', '225.12'], $student->paymentPlan->installments()->pluck('planned_amount')->all());
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole($this->ownerRole);
        return $user;
    }

    private function category(array $overrides = []): VehicleCategory
    {
        return VehicleCategory::create(array_merge(['category_code' => 'CAT'.Str::upper(Str::random(6)), 'category_name' => 'Test Category', 'training_hours' => 10, 'price' => '1000.00', 'is_active' => true], $overrides));
    }

    private function student(): Student
    {
        return Student::create(['student_number' => 'DS-'.Str::upper(Str::random(12)), 'full_name' => 'Payments Student', 'address' => 'Address', 'nic_number' => 'NIC-'.Str::upper(Str::random(10)), 'date_of_birth' => '2000-01-01', 'contact_number' => '0771234567', 'status' => Student::STATUS_ACTIVE]);
    }
}

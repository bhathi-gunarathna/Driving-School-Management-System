<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Finance\IncomeReport;
use App\Livewire\Admin\Finance\OutstandingReport;
use App\Livewire\Admin\Finance\Overview;
use App\Models\Student;
use App\Models\StudentPaymentPlan;
use App\Models\User;
use App\Services\StudentPaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinancialReportsTest extends TestCase
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

    public function test_financial_overview_uses_plans_for_fees_and_actual_receipts_for_income(): void
    {
        $owner = $this->owner();
        $studentA = $this->student('A');
        $studentB = $this->student('B');
        $planA = app(StudentPaymentService::class)->createPlan($studentA, 10000, StudentPaymentPlan::TYPE_TWO_INSTALLMENTS);
        $planB = app(StudentPaymentService::class)->createPlan($studentB, 20000, StudentPaymentPlan::TYPE_FULL);
        $this->receipt($planA, $owner, '25.00', 'cash');
        $this->receipt($planB, $owner, '200.00', 'card');

        Livewire::actingAs($owner)->test(Overview::class)
            ->assertSee('Total Course Fees')->assertSee('LKR 300.00')
            ->assertSee('LKR 225.00')->assertSee('LKR 75.00')
            ->assertSee('Fully Paid Students')->assertSee('Partially Paid Students');
    }

    public function test_income_report_filters_actual_transaction_dates_and_payment_methods(): void
    {
        $owner = $this->owner();
        $student = $this->student('Filter');
        $plan = app(StudentPaymentService::class)->createPlan($student, 50000, StudentPaymentPlan::TYPE_THREE_INSTALLMENTS);
        $todayReceipt = $this->receipt($plan, $owner, '20.00', 'cash', today()->toDateString());
        $yesterdayReceipt = $this->receipt($plan, $owner, '30.00', 'card', today()->subDay()->toDateString());

        Livewire::actingAs($owner)->test(IncomeReport::class)
            ->set('period', 'today')->assertSee('LKR 20.00')->assertSee($todayReceipt->receipt_number)->assertDontSee($yesterdayReceipt->receipt_number)
            ->set('period', 'custom')->set('startDate', today()->subDay()->toDateString())->set('endDate', today()->toDateString())
            ->set('methodFilter', 'card')->assertSee('LKR 30.00')->assertSee($yesterdayReceipt->receipt_number)->assertDontSee($todayReceipt->receipt_number);
    }

    public function test_daily_and_monthly_summaries_use_receipt_dates_and_details_filter_transactions(): void
    {
        $owner = $this->owner();
        $student = $this->student('Daily');
        $plan = app(StudentPaymentService::class)->createPlan($student, 50000, StudentPaymentPlan::TYPE_THREE_INSTALLMENTS);
        $first = $this->receipt($plan, $owner, '12.50', 'cash', today()->toDateString());
        $second = $this->receipt($plan, $owner, '7.50', 'cash', today()->toDateString());

        $component = Livewire::actingAs($owner)->test(IncomeReport::class)->set('period', 'this_month')
            ->assertSee('LKR 20.00')->assertSee('Daily Income')->assertSee('Monthly Income');
        $component->call('showDate', today()->toDateString())->assertSee($first->receipt_number)->assertSee($second->receipt_number);
        $component->call('showMonth', today()->format('Y-m'))->assertSee($first->receipt_number)->assertSee($second->receipt_number);
    }

    public function test_transaction_search_sorting_and_outstanding_report_are_filter_aware(): void
    {
        $owner = $this->owner();
        $studentA = $this->student('Alpha');
        $studentB = $this->student('Beta');
        $planA = app(StudentPaymentService::class)->createPlan($studentA, 10000, StudentPaymentPlan::TYPE_TWO_INSTALLMENTS);
        $planB = app(StudentPaymentService::class)->createPlan($studentB, 50000, StudentPaymentPlan::TYPE_THREE_INSTALLMENTS);
        $paymentA = $this->receipt($planA, $owner, '10.00', 'bank_transfer');
        $this->receipt($planB, $owner, '5.00', 'cash');

        Livewire::actingAs($owner)->test(IncomeReport::class)->set('period', 'this_month')->set('search', $paymentA->receipt_number)
            ->assertSee($paymentA->receipt_number)->assertDontSee($studentB->student_number)->call('sortBy', 'amount');

        Livewire::actingAs($owner)->test(OutstandingReport::class)->assertSee($studentA->student_number)->assertSee($studentB->student_number)
            ->assertSee('LKR 90.00')->assertSee('LKR 495.00')
            ->set('search', $studentA->student_number)->assertSee($studentA->student_number)->assertDontSee($studentB->student_number);
    }

    public function test_csv_exports_follow_selected_filters_and_require_export_permission(): void
    {
        $owner = $this->owner();
        $student = $this->student('Csv');
        $plan = app(StudentPaymentService::class)->createPlan($student, 10000, StudentPaymentPlan::TYPE_FULL);
        $receipt = $this->receipt($plan, $owner, '10.00', 'cash');

        $response = $this->actingAs($owner)->get(route('admin.finance.exports.transactions', [
            'period' => 'today', 'method' => 'cash', 'search' => $receipt->receipt_number,
        ]));
        $response->assertOk()->assertDownload('payment-transactions.csv');
        $this->assertStringContainsString($receipt->receipt_number, $response->streamedContent());
        $this->assertStringContainsString($student->student_number, $response->streamedContent());

        $this->ownerRole->revokePermissionTo('financial-reports.export');
        $this->actingAs($owner)->get(route('admin.finance.exports.outstanding'))->assertForbidden();
    }

    public function test_owner_dashboard_shows_real_financial_values_and_coach_cannot_access_finance(): void
    {
        $owner = $this->owner();
        $student = $this->student('Dash');
        $plan = app(StudentPaymentService::class)->createPlan($student, 10000, StudentPaymentPlan::TYPE_FULL);
        $payment = $this->receipt($plan, $owner, '35.00', 'cash');

        Livewire::actingAs($owner)->test(Dashboard::class)
            ->assertSee('LKR 35.00')->assertSee('LKR 65.00')->assertSee($payment->receipt_number)
            ->assertSee('Vehicle Management')
            ->assertSee('Manage school vehicles, vehicle details, insurance and revenue license documents.')
            ->assertSee(route('admin.vehicles.index'), false);
        Livewire::actingAs($owner)->test(Overview::class)->assertSee('Payment Method Distribution');

        $coach = User::factory()->create();
        $coach->assignRole($this->coachRole);
        $this->actingAs($coach)->get(route('admin.finance.overview'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.finance.income'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.finance.outstanding'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.finance.exports.transactions'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_vehicle_dashboard_quick_access_requires_vehicle_view_permission(): void
    {
        $owner = $this->owner();
        $this->ownerRole->revokePermissionTo('vehicles.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Livewire::actingAs($owner)->test(Dashboard::class)
            ->assertDontSee('Vehicle Management')
            ->assertDontSee(route('admin.vehicles.index'), false);
    }

    public function test_empty_financial_reports_render_zero_values(): void
    {
        $owner = $this->owner();
        Livewire::actingAs($owner)->test(Overview::class)->assertSee('LKR 0.00');
        Livewire::actingAs($owner)->test(IncomeReport::class)->assertSee('LKR 0.00')->assertSee('No transactions for this method/date filter.');
        Livewire::actingAs($owner)->test(OutstandingReport::class)->assertSee('LKR 0.00')->assertSee('No outstanding student balances');
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole($this->ownerRole);
        return $user;
    }

    private function student(string $suffix): Student
    {
        return Student::create([
            'student_number' => 'DS-'.Str::upper($suffix).'-'.Str::upper(Str::random(5)),
            'full_name' => $suffix.' Student', 'address' => 'Report Test Address',
            'nic_number' => 'NIC-'.Str::upper(Str::random(12)), 'date_of_birth' => '2000-01-01',
            'contact_number' => '0771234567', 'status' => Student::STATUS_ACTIVE,
        ]);
    }

    private function receipt(StudentPaymentPlan $plan, User $owner, string $amount, string $method, ?string $date = null): \App\Models\StudentPayment
    {
        return app(StudentPaymentService::class)->recordPayment($plan->id, $owner, [
            'amount' => $amount, 'payment_method' => $method, 'payment_date' => $date ?? today()->toDateString(),
        ]);
    }
}

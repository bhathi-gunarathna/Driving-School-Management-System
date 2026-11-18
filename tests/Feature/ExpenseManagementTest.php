<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Expenses\Categories;
use App\Livewire\Admin\Expenses\Create;
use App\Livewire\Admin\Expenses\Edit;
use App\Livewire\Admin\Expenses\Index;
use App\Livewire\Admin\Expenses\Reports;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    private Role $ownerRole;
    private Role $coachRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ExpenseCategorySeeder::class);
        $this->ownerRole = Role::findByName('Owner', 'web');
        $this->coachRole = Role::findByName('Coach', 'web');
        Storage::fake('local');
    }

    public function test_owner_can_create_expense_with_optional_vehicle_and_private_receipt(): void
    {
        $owner = $this->owner();
        $category = $this->category('Other Expenses');
        $vehicle = $this->vehicle();

        $component = Livewire::actingAs($owner)->test(Create::class)
            ->set('expense_category_id', (string) $category->id)
            ->set('vehicle_id', (string) $vehicle->id)
            ->set('title', 'Emergency tire repair')
            ->set('amount', '2500.50')
            ->set('expense_date', '2026-11-15')
            ->set('payment_method', 'cash')
            ->set('paid_to', 'Roadside Garage')
            ->set('description', 'Repaired front tire')
            ->set('receipt', UploadedFile::fake()->create('receipt.jpg', 10, 'image/jpeg'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $expense = Expense::query()->where('title', 'Emergency tire repair')->firstOrFail();
        $this->assertSame('2500.50', $expense->amount);
        $this->assertSame($vehicle->id, $expense->vehicle_id);
        $this->assertSame($owner->id, $expense->created_by);
        $this->assertStringStartsWith('expenses/'.$expense->id.'/', $expense->receipt_path);
        Storage::disk('local')->assertExists($expense->receipt_path);
        $this->assertDatabaseHas('audit_logs', ['module' => 'expenses', 'related_id' => $expense->id, 'action' => 'created']);
        $audit = \App\Models\AuditLog::query()->where('module', 'expenses')->where('related_id', $expense->id)->firstOrFail();
        $this->assertStringNotContainsString('receipt_path', json_encode($audit->new_values));

        Livewire::actingAs($owner)->test(Create::class)
            ->set('expense_category_id', (string) $category->id)->set('title', 'Petty cash expense')
            ->set('amount', '125.25')->set('expense_date', '2026-11-15')->set('payment_method', 'cash')
            ->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('expenses', ['title' => 'Petty cash expense', 'vehicle_id' => null]);
    }

    public function test_expense_creation_validates_category_amount_payment_method_and_receipt(): void
    {
        $owner = $this->owner();
        $inactive = ExpenseCategory::query()->create(['name' => 'Inactive Category', 'is_active' => false]);

        Livewire::actingAs($owner)->test(Create::class)
            ->set('expense_category_id', (string) $inactive->id)
            ->set('title', 'Invalid amount')
            ->set('amount', '0')
            ->set('expense_date', '2026-11-18')
            ->set('payment_method', 'unknown')
            ->set('receipt', UploadedFile::fake()->create('receipt.exe', 30, 'application/octet-stream'))
            ->call('save')
            ->assertHasErrors(['expense_category_id', 'amount', 'payment_method', 'receipt']);

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_owner_can_edit_expense_and_preserve_or_replace_receipt_safely(): void
    {
        $owner = $this->owner();
        $vehicle = $this->vehicle();
        $oldPath = 'expenses/1/old-receipt.pdf';
        Storage::disk('local')->put($oldPath, '%PDF test');
        $expense = $this->expense($owner, ['vehicle_id' => $vehicle->id, 'receipt_path' => $oldPath]);

        Livewire::actingAs($owner)->test(Edit::class, ['expense' => $expense])
            ->set('amount', '999.99')->set('vehicle_id', '')->call('save')->assertHasNoErrors()->assertRedirect();

        $expense->refresh();
        $this->assertSame('999.99', $expense->amount);
        $this->assertNull($expense->vehicle_id);
        $this->assertSame($oldPath, $expense->receipt_path);
        Storage::disk('local')->assertExists($oldPath);

        Livewire::actingAs($owner)->test(Edit::class, ['expense' => $expense])
            ->set('receipt', UploadedFile::fake()->create('new-bill.pdf', 10, 'application/pdf'))
            ->call('save')->assertHasNoErrors();
        $expense->refresh();
        $this->assertNotSame($oldPath, $expense->receipt_path);
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($expense->receipt_path);
    }

    public function test_receipt_is_downloaded_only_by_authorized_owner(): void
    {
        $owner = $this->owner();
        $expense = $this->expense($owner);
        $path = 'expenses/'.$expense->id.'/receipt.pdf';
        Storage::disk('local')->put($path, '%PDF receipt');
        $expense->update(['receipt_path' => $path]);

        $response = $this->actingAs($owner)->get(route('admin.expenses.receipt', $expense));
        $response->assertOk();
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('private', $response->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', $response->headers->get('cache-control'));

        $coach = $this->coach();
        $this->actingAs($coach)->get(route('admin.expenses.receipt', $expense))->assertForbidden();
    }

    public function test_categories_can_be_managed_and_referenced_categories_cannot_be_deleted(): void
    {
        $owner = $this->owner();
        $category = $this->category('Petty Cash');
        $expense = $this->expense($owner, ['expense_category_id' => $category->id]);

        Livewire::actingAs($owner)->test(Categories::class)
            ->call('deleteCategory', $category->id)
            ->assertHasErrors('categoryDelete');
        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);

        Livewire::actingAs($owner)->test(Categories::class)
            ->set('name', 'Petty Cash')->call('saveCategory')->assertHasErrors('name')
            ->set('name', 'Office Running Costs')->call('saveCategory')->assertHasNoErrors();
        $this->assertDatabaseHas('expense_categories', ['name' => 'Office Running Costs']);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'expense_category_id' => $category->id]);

        $unused = ExpenseCategory::query()->create(['name' => 'Unused Category', 'is_active' => true]);
        Livewire::actingAs($owner)->test(Categories::class)->call('deleteCategory', $unused->id);
        $this->assertDatabaseMissing('expense_categories', ['id' => $unused->id]);
    }

    public function test_expense_deletion_keeps_an_audit_snapshot_and_removes_private_receipt(): void
    {
        $owner = $this->owner();
        $expense = $this->expense($owner);
        $path = 'expenses/'.$expense->id.'/receipt.pdf';
        Storage::disk('local')->put($path, '%PDF receipt');
        $expense->update(['receipt_path' => $path]);

        Livewire::actingAs($owner)->test(Index::class)->call('deleteExpense', $expense->id);

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
        $audit = \App\Models\AuditLog::query()->where('module', 'expenses')->where('related_id', $expense->id)->where('action', 'deleted')->firstOrFail();
        $this->assertSame('Test expense', substr($audit->previous_values['title'], 0, strlen('Test expense')));
        $this->assertArrayNotHasKey('receipt_path', $audit->previous_values);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_expense_list_filters_by_search_category_vehicle_method_and_date_range(): void
    {
        $owner = $this->owner();
        $vehicle = $this->vehicle();
        $otherVehicle = $this->vehicle();
        $repair = $this->category('Vehicle Repair & Maintenance');
        $office = $this->category('Office Expenses');
        $target = $this->expense($owner, ['title' => 'Brake service', 'expense_category_id' => $repair->id, 'vehicle_id' => $vehicle->id, 'payment_method' => 'cash', 'expense_date' => '2026-11-10']);
        $other = $this->expense($owner, ['title' => 'Office supplies', 'expense_category_id' => $office->id, 'vehicle_id' => $otherVehicle->id, 'payment_method' => 'card', 'expense_date' => '2026-10-02']);

        Livewire::actingAs($owner)->test(Index::class)
            ->assertSee($target->title)->assertSee($other->title)
            ->set('search', 'Brake')->assertSee($target->title)->assertDontSee($other->title)
            ->set('search', '')->set('categoryFilter', (string) $repair->id)->assertSee($target->title)->assertDontSee($other->title)
            ->set('categoryFilter', '')->set('vehicleFilter', (string) $vehicle->id)->assertSee($target->title)->assertDontSee($other->title)
            ->set('vehicleFilter', '')->set('methodFilter', 'cash')->assertSee($target->title)->assertDontSee($other->title)
            ->set('methodFilter', '')->set('dateFrom', '2026-11-01')->set('dateTo', '2026-11-30')->assertSee($target->title)->assertDontSee($other->title);
    }

    public function test_reports_support_daily_monthly_yearly_category_vehicle_and_custom_range(): void
    {
        $owner = $this->owner();
        $vehicle = $this->vehicle();
        $category = $this->category('Fuel Expenses');
        $first = $this->expense($owner, ['title' => 'Fuel 1', 'amount' => '100.00', 'expense_category_id' => $category->id, 'vehicle_id' => $vehicle->id, 'expense_date' => '2026-11-01']);
        $second = $this->expense($owner, ['title' => 'Fuel 2', 'amount' => '250.00', 'expense_category_id' => $category->id, 'vehicle_id' => $vehicle->id, 'expense_date' => '2026-11-15']);

        $report = Livewire::actingAs($owner)->test(Reports::class)
            ->set('startDate', '2026-11-01')->set('endDate', '2026-11-30')
            ->assertSee('LKR 350.00')->assertSee('Daily Expenses');
        $report->set('groupBy', 'monthly')->assertSee('2026-11');
        $report->set('groupBy', 'yearly')->assertSee('2026');
        $report->set('groupBy', 'category')->assertSee('Fuel Expenses');
        $report->set('groupBy', 'vehicle')->assertSee($vehicle->registration_number);

        $this->get(route('admin.expenses.export', ['group' => 'category', 'startDate' => '2026-11-01', 'endDate' => '2026-11-30']))
            ->assertOk()->assertDownload('expenses-category-report.csv');
        $this->assertDatabaseHas('expenses', ['id' => $first->id]);
        $this->assertDatabaseHas('expenses', ['id' => $second->id]);
    }

    public function test_dashboard_expense_summary_is_separate_from_student_payments(): void
    {
        $owner = $this->owner();
        $today = Carbon::today()->toDateString();
        $this->expense($owner, ['amount' => '100.00', 'expense_date' => $today, 'vehicle_id' => $this->vehicle()->id]);
        $this->expense($owner, ['amount' => '25.00', 'expense_date' => $today, 'expense_category_id' => $this->category('Petty Cash')->id]);

        Livewire::actingAs($owner)->test(Dashboard::class)
            ->assertSee('Expense Summary')->assertSee("Today's Expenses")
            ->assertSee('Vehicle-related Expenses')->assertSee('Petty Cash Expenses')
            ->assertSee('LKR 125.00')->assertSee(route('admin.expenses.reports'), false);
    }

    public function test_expense_routes_and_actions_are_owner_permission_restricted(): void
    {
        $coach = $this->coach();
        $this->actingAs($coach)->get(route('admin.expenses.index'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.expenses.create'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.expenses.categories'))->assertForbidden();

        $owner = $this->owner();
        $expense = $this->expense($owner);
        $this->ownerRole->revokePermissionTo('expenses.delete');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Livewire::actingAs($owner)->test(Index::class)->call('deleteExpense', $expense->id)->assertForbidden();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole($this->ownerRole);

        return $user;
    }

    private function coach(): User
    {
        $user = User::factory()->create();
        $user->assignRole($this->coachRole);

        return $user;
    }

    private function category(string $name): ExpenseCategory
    {
        return ExpenseCategory::query()->where('name', $name)->firstOrFail();
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::query()->create([
            'registration_number' => 'TEST-'.Str::upper(Str::random(7)), 'brand' => 'Test', 'model' => 'Car',
            'fuel_type' => 'petrol', 'transmission_type' => 'manual', 'current_mileage' => '100.0', 'status' => 'active',
        ]);
    }

    private function expense(User $owner, array $attributes = []): Expense
    {
        return Expense::query()->create(array_merge([
            'expense_category_id' => $this->category('Other Expenses')->id,
            'title' => 'Test expense '.Str::random(5), 'amount' => '10.00',
            'expense_date' => '2026-11-18', 'payment_method' => 'cash', 'created_by' => $owner->id,
        ], $attributes));
    }
}

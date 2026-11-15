<?php

namespace Tests\Feature;

use App\Livewire\Admin\Audit\Index as AuditIndex;
use App\Livewire\Admin\Settings\Edit as SettingsEdit;
use App\Models\Student;
use App\Models\StudentPaymentPlan;
use App\Models\StudentVehicleCategory;
use App\Models\SystemSetting;
use App\Models\TrainingCoachAssignment;
use App\Models\Trial;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Notifications\SchoolNotification;
use App\Services\SchoolNotificationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemFinalizationTest extends TestCase
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

    public function test_owner_can_update_school_settings_and_upload_public_logo(): void
    {
        Storage::fake('public');
        $owner = $this->user('Owner');
        Livewire::actingAs($owner)->test(SettingsEdit::class)
            ->set('school_name', 'Colombo Driving Academy')
            ->set('address', 'Colombo')
            ->set('contact_number', '0111234567')
            ->set('email', 'school@example.test')
            ->set('website', 'https://school.example.test')
            ->set('timezone', 'Asia/Colombo')
            ->set('currency', 'LKR')
            ->set('date_format', 'Y-m-d')
            ->set('receipt_prefix', 'CDA-REC')
            ->set('student_number_prefix', 'CDA')
            ->set('logo', UploadedFile::fake()->createWithContent('school.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jKxkAAAAASUVORK5CYII=')))
            ->call('saveSettings')->assertHasNoErrors();

        $settings = SystemSetting::firstOrFail();
        $this->assertSame('CDA', $settings->student_number_prefix);
        $this->assertSame('Asia/Colombo', $settings->timezone);
        Storage::disk('public')->assertExists($settings->logo_path);
        $this->assertDatabaseHas('audit_logs', ['module' => 'settings', 'action' => 'created']);

        $category = VehicleCategory::create(['category_code' => 'PFX', 'category_name' => 'Prefix Test', 'training_hours' => 10, 'price' => 1000, 'is_active' => true]);
        Livewire::actingAs($owner)->test(\App\Livewire\Admin\Students\Create::class)
            ->set('full_name', 'Prefix Student')->set('address', 'Colombo')->set('nic_number', 'NIC-PREFIX-0001')
            ->set('date_of_birth', '2000-01-01')->set('contact_number', '0771000001')
            ->set('selectedCategoryIds', [$category->id])->call('registerStudent')->assertHasNoErrors();
        $student = Student::where('nic_number', 'NIC-PREFIX-0001')->firstOrFail();
        $this->assertStringStartsWith('CDA-'.now()->format('Y').'-', $student->student_number);
        $receipt = app(\App\Services\StudentPaymentService::class)->recordPayment($student->paymentPlan->id, $owner, [
            'amount' => '10.00', 'payment_date' => now()->toDateString(), 'payment_method' => 'cash',
        ]);
        $this->assertStringStartsWith('CDA-REC-'.now()->format('Y').'-', $receipt->receipt_number);
        $this->actingAs($owner)->get(route('admin.dashboard'))->assertOk()->assertSee(now('Asia/Colombo')->format('Y-m-d'));
    }

    public function test_settings_validation_and_coach_access_are_restricted(): void
    {
        $owner = $this->user('Owner');
        Livewire::actingAs($owner)->test(SettingsEdit::class)
            ->set('school_name', '')->set('currency', 'NOT-A-CURRENCY')
            ->call('saveSettings')->assertHasErrors(['school_name', 'currency']);

        $coach = $this->user('Coach');
        $this->actingAs($coach)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.audit.index'))->assertForbidden();
    }

    public function test_audit_logs_record_actor_and_skip_private_medical_report_path(): void
    {
        $owner = $this->user('Owner', 'Audit Owner');
        $this->actingAs($owner);
        $student = Student::create([
            'student_number' => 'DS-2026-9911', 'full_name' => 'Sample Student', 'address' => 'Colombo',
            'nic_number' => 'NIC-AUDIT-9911', 'date_of_birth' => '2000-01-01', 'contact_number' => '0771112233',
            'medical_report_path' => 'medical-reports/secret-file.png', 'status' => Student::STATUS_ACTIVE,
        ]);

        $log = \App\Models\AuditLog::where('related_type', 'Student')->where('related_id', $student->id)->firstOrFail();
        $this->assertSame('Audit Owner', $log->user_name);
        $this->assertArrayNotHasKey('medical_report_path', $log->new_values);
        $student->update(['full_name' => 'Updated Sample Student']);
        $updateLog = \App\Models\AuditLog::where('related_type', 'Student')->where('related_id', $student->id)->latest('id')->firstOrFail();
        $this->assertSame('Sample Student', $updateLog->previous_values['full_name']);
        $this->assertSame('Updated Sample Student', $updateLog->new_values['full_name']);
        Livewire::actingAs($owner)->test(AuditIndex::class)->assertSee('Sample Student')->assertSee('Audit Owner');
        $this->actingAs($owner)->get(route('admin.audit.index'))->assertOk();
    }

    public function test_database_notifications_are_deduplicated_and_users_can_mark_only_their_own_read(): void
    {
        $owner = $this->user('Owner');
        $coach = $this->user('Coach');
        $service = app(SchoolNotificationService::class);
        $service->sendOnce($coach, 'assignment:42', 'New student assignment', 'Assigned student', route('coach.training.index'), 'assignment');
        $service->sendOnce($coach, 'assignment:42', 'New student assignment', 'Assigned student', route('coach.training.index'), 'assignment');
        $owner->notify(new SchoolNotification('test:owner', 'Owner only', 'Private reminder', route('admin.finance.outstanding'), 'payment'));

        $this->assertSame(1, $coach->notifications()->count());
        $this->assertSame(1, $owner->notifications()->count());
        $this->actingAs($coach)->get(route('notifications.index'))->assertOk()->assertSee('Assigned student')->assertDontSee('Private reminder');
        $id = $coach->notifications()->firstOrFail()->id;
        Livewire::actingAs($coach)->test(\App\Livewire\Notifications\Index::class)->call('markAsRead', $id);
        $this->assertNotNull($coach->notifications()->findOrFail($id)->read_at);
    }

    public function test_scheduled_notifications_cover_trials_incomplete_training_and_owner_payment_follow_up(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-15 08:00:00', 'Asia/Colombo'));
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 08:00:00', 'Asia/Colombo'));
        try {
            $owner = $this->user('Owner');
            $coach = $this->user('Coach');
            $student = Student::create([
                'student_number' => 'DS-2026-NOTIFY1', 'full_name' => 'Trial Student', 'address' => 'Colombo',
                'nic_number' => 'NIC-NOTIFY-0001', 'date_of_birth' => '2000-01-01', 'contact_number' => '0771000000', 'status' => Student::STATUS_ACTIVE,
            ]);
            $category = VehicleCategory::create(['category_code' => 'T1', 'category_name' => 'Test Category', 'training_hours' => 10, 'price' => 1000, 'is_active' => true]);
            $enrollment = StudentVehicleCategory::create(['student_id' => $student->id, 'vehicle_category_id' => $category->id, 'agreed_fee' => 1000, 'required_training_hours' => 10, 'status' => 'active']);
            TrainingCoachAssignment::create(['student_vehicle_category_id' => $enrollment->id, 'coach_id' => $coach->id, 'assigned_by' => $owner->id, 'status' => 'active', 'assigned_at' => now()]);
            $trial = Trial::create(['trial_number' => 'DS-TRIAL-NOTIFY1', 'trial_date' => '2026-11-17', 'reporting_time' => '09:00', 'location' => 'Test Center', 'status' => Trial::STATUS_SCHEDULED, 'created_by' => $owner->id]);
            $this->assertSame('08:00', CarbonImmutable::now('Asia/Colombo')->format('H:i'));
            $this->assertSame(1, Trial::upcoming()->count());
            app(\App\Services\TrialService::class)->assignStudent($trial, $student, [$enrollment->id]);
            StudentPaymentPlan::create(['student_id' => $student->id, 'total_agreed_fee' => 1000, 'plan_type' => StudentPaymentPlan::TYPE_FULL, 'number_of_installments' => 1, 'total_amount_paid' => 0, 'outstanding_balance' => 1000, 'payment_status' => StudentPaymentPlan::STATUS_PENDING]);

            $this->artisan('school:send-notifications')->assertSuccessful();
            $ownerCategories = $owner->notifications()->get()->pluck('data.category');
            $coachCategories = $coach->notifications()->get()->pluck('data.category');
            $this->assertContains('trial', $ownerCategories);
            $this->assertContains('training', $ownerCategories);
            $this->assertContains('payment', $ownerCategories);
            $this->assertContains('trial', $coachCategories);
            $this->assertContains('training', $coachCategories);
            $this->assertNotContains('payment', $coachCategories);

            $this->artisan('school:send-notifications')->assertSuccessful();
            $this->assertSame(3, $owner->notifications()->count());
            $this->assertSame(2, $coach->notifications()->count());
        } finally {
            Carbon::setTestNow();
            CarbonImmutable::setTestNow();
        }
    }

    private function user(string $role, string $name = 'System Test User'): User
    {
        $user = User::factory()->create(['name' => $name, 'is_active' => true]);
        $user->assignRole($role === 'Owner' ? $this->ownerRole : $this->coachRole);
        return $user;
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Vehicles\Expiry;
use App\Livewire\Admin\Vehicles\Index as VehicleIndex;
use App\Livewire\Admin\Vehicles\Notifications as VehicleNotifications;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\SchoolNotification;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VehicleExpiryAlertsTest extends TestCase
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
        SystemSetting::query()->create(['timezone' => 'Asia/Colombo']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_scheduler_sends_30_14_7_day_and_expired_alerts_for_both_document_types(): void
    {
        $this->freezeAtEight();
        $owner = $this->owner();

        foreach ([30, 14, 7, -1] as $days) {
            $expiryDate = Carbon::now('Asia/Colombo')->startOfDay()->addDays($days)->toDateString();
            $this->vehicle([
                'registration_number' => 'VH'.str_pad((string) abs($days), 3, '0', STR_PAD_LEFT).($days < 0 ? 'X' : 'D'),
                'insurance_expiry_date' => $expiryDate,
                'revenue_license_expiry_date' => $expiryDate,
            ]);
        }

        Artisan::call('school:send-notifications');

        $notifications = $owner->notifications()->get();
        $this->assertCount(8, $notifications);
        foreach (['insurance', 'revenue_license'] as $type) {
            foreach (['30', '14', '7', 'expired'] as $alertType) {
                $this->assertTrue($notifications->contains(fn ($notification) => $notification->data['document_type'] === $type && $notification->data['alert_type'] === $alertType));
            }
        }
        $this->assertSame(8, $owner->notifications()->where('data->category', 'vehicle')->count());
    }

    public function test_scheduler_deduplicates_repeats_excludes_inactive_vehicles_and_notifies_maintenance_vehicles(): void
    {
        $this->freezeAtEight();
        $owner = $this->owner();
        $expiry = Carbon::now('Asia/Colombo')->startOfDay()->addDays(14)->toDateString();
        $this->vehicle(['registration_number' => 'ACTIVE-1', 'insurance_expiry_date' => $expiry]);
        $this->vehicle(['registration_number' => 'MAINT-1', 'status' => 'maintenance', 'revenue_license_expiry_date' => $expiry]);
        $this->vehicle(['registration_number' => 'INACTIVE-1', 'status' => 'inactive', 'insurance_expiry_date' => $expiry, 'revenue_license_expiry_date' => $expiry]);

        Artisan::call('school:send-notifications');
        Artisan::call('school:send-notifications');

        $this->assertSame(2, $owner->notifications()->where('data->category', 'vehicle')->count());
        $this->assertDatabaseCount('notification_dispatches', 2);
        $this->assertFalse($owner->notifications()->where('data->registration_number', 'INACTIVE-1')->exists());
        $this->assertTrue($owner->notifications()->where('data->registration_number', 'MAINT-1')->exists());
    }

    public function test_renewing_a_document_creates_a_new_alert_cycle_without_removing_history(): void
    {
        $this->freezeAtEight();
        $owner = $this->owner();
        $vehicle = $this->vehicle(['insurance_expiry_date' => Carbon::now('Asia/Colombo')->startOfDay()->addDays(30)->toDateString()]);

        Artisan::call('school:send-notifications');
        $vehicle->update(['insurance_expiry_date' => Carbon::now('Asia/Colombo')->startOfDay()->addDays(60)->toDateString()]);
        Artisan::call('school:send-notifications');

        $this->assertSame(1, $owner->notifications()->where('data->category', 'vehicle')->count());
        $this->assertSame(1, $owner->notifications()->where('data->alert_type', '30')->count());
        $this->assertDatabaseCount('notification_dispatches', 1);
        $this->assertSame(1, $owner->notifications()->count());
    }

    public function test_dashboard_summarizes_operational_vehicle_documents_and_links_alerts(): void
    {
        $owner = $this->owner();
        $today = Carbon::now('Asia/Colombo')->startOfDay();
        $this->vehicle(['registration_number' => 'DASH-ACT', 'insurance_expiry_date' => $today->copy()->addDays(10)->toDateString(), 'revenue_license_expiry_date' => $today->copy()->subDays(2)->toDateString()]);
        $this->vehicle(['registration_number' => 'DASH-MNT', 'status' => 'maintenance', 'revenue_license_expiry_date' => $today->copy()->addDays(20)->toDateString()]);
        $this->vehicle(['registration_number' => 'DASH-INACTIVE', 'status' => 'inactive', 'insurance_expiry_date' => $today->copy()->addDays(5)->toDateString()]);

        Livewire::actingAs($owner)->test(Dashboard::class)
            ->assertSee('Vehicle Document Alerts')
            ->assertSee('Total Active Vehicles')
            ->assertSee('Documents Expiring Soon')
            ->assertSee('Insurance Expiring')
            ->assertSee('Revenue Licenses Expiring')
            ->assertSee('2')
            ->assertSee('3')
            ->assertSee('DASH-ACT')
            ->assertSee('DASH-MNT')
            ->assertDontSee('DASH-INACTIVE')
            ->assertSee(route('admin.vehicles.expiry'), false)
            ->assertSee(route('admin.vehicles.notifications'), false);
    }

    public function test_expiry_management_filters_search_type_status_and_date_range(): void
    {
        $owner = $this->owner();
        $today = Carbon::now('Asia/Colombo')->startOfDay();
        $this->vehicle(['registration_number' => 'EXP-INS', 'insurance_expiry_date' => $today->copy()->addDays(7)->toDateString()]);
        $this->vehicle(['registration_number' => 'OLD-LIC', 'revenue_license_expiry_date' => $today->copy()->subDay()->toDateString()]);
        $this->vehicle(['registration_number' => 'FUTURE-INS', 'insurance_expiry_date' => $today->copy()->addDays(90)->toDateString()]);

        Livewire::actingAs($owner)->test(Expiry::class)
            ->assertSee('EXP-INS')->assertSee('OLD-LIC')->assertSee('FUTURE-INS')
            ->set('expiryFilter', 'expiring')->assertSee('EXP-INS')->assertDontSee('OLD-LIC')->assertDontSee('FUTURE-INS')
            ->set('expiryFilter', 'expired')->assertSee('OLD-LIC')->assertDontSee('EXP-INS')
            ->set('expiryFilter', 'valid')->assertSee('FUTURE-INS')->assertDontSee('OLD-LIC')
            ->set('expiryFilter', 'all')->set('documentType', 'insurance')->assertSee('EXP-INS')->assertDontSee('OLD-LIC')
            ->set('search', 'FUTURE')->assertSee('FUTURE-INS')->assertDontSee('EXP-INS')
            ->set('search', '')->set('dateFrom', $today->copy()->addDays(80)->toDateString())->set('dateTo', $today->copy()->addDays(100)->toDateString())
            ->assertSee('FUTURE-INS')->assertDontSee('EXP-INS');
    }

    public function test_vehicle_notification_history_reuses_account_notifications_and_can_mark_read(): void
    {
        $owner = $this->owner();
        $owner->notify(new SchoolNotification('vehicle-document-expiry:1:insurance:2026-11-28:7', 'Insurance expires soon', 'Vehicle ABC-123 insurance expires.', route('admin.vehicles.index'), 'vehicle', [
            'registration_number' => 'ABC-123', 'document_type' => 'insurance', 'alert_type' => '7', 'expiry_date' => '2026-11-28',
        ]));
        $owner->notify(new SchoolNotification('trial-upcoming:1', 'Upcoming trial', 'Trial is scheduled.', route('admin.dashboard'), 'trial'));

        $notification = $owner->notifications()->where('data->category', 'vehicle')->firstOrFail();
        Livewire::actingAs($owner)->test(VehicleNotifications::class)
            ->assertSee('Vehicle Expiry Notification History')
            ->assertSee('ABC-123')->assertSee('Insurance')->assertSee('7-day reminder')
            ->assertDontSee('Upcoming trial')
            ->call('markAsRead', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_vehicle_expiry_pages_and_actions_are_restricted_to_owners_with_view_permission(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole($this->coachRole);
        $this->actingAs($coach)->get(route('admin.vehicles.expiry'))->assertForbidden();
        $this->actingAs($coach)->get(route('admin.vehicles.notifications'))->assertForbidden();

        $owner = $this->owner();
        $this->ownerRole->revokePermissionTo('vehicles.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($owner)->get(route('admin.vehicles.expiry'))->assertForbidden();
        Livewire::actingAs($owner)->test(Dashboard::class)->assertDontSee('Vehicle Document Alerts');
    }

    public function test_existing_vehicle_list_remains_available_and_scheduler_runs_every_minute(): void
    {
        $owner = $this->owner();
        $vehicle = $this->vehicle(['registration_number' => 'EXISTING-1']);
        Livewire::actingAs($owner)->test(VehicleIndex::class)->assertSee($vehicle->registration_number);

        $event = collect(Schedule::events())->first(fn ($event) => str_contains((string) $event->command, 'school:send-notifications'));
        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    private function freezeAtEight(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 08:00:00', 'Asia/Colombo'));
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole($this->ownerRole);

        return $user;
    }

    private function vehicle(array $attributes = []): Vehicle
    {
        static $sequence = 0;
        $sequence++;

        return Vehicle::query()->create(array_merge([
            'registration_number' => 'TEST-'.Str::upper(Str::random(8)),
            'brand' => 'Test', 'model' => 'Vehicle', 'fuel_type' => 'petrol',
            'transmission_type' => 'manual', 'current_mileage' => '100.0', 'status' => 'active',
        ], $attributes));
    }
}

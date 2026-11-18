<?php

namespace Tests\Feature;

use App\Livewire\Admin\Coaches\Edit;
use App\Livewire\Admin\Coaches\Index;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CoachManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Role $ownerRole;

    protected Role $coachRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $viewPermission = Permission::findOrCreate('coaches.view', 'web');
        $managePermission = Permission::findOrCreate('coaches.manage', 'web');

        $this->ownerRole = Role::findOrCreate('Owner', 'web');
        $this->ownerRole->givePermissionTo([$viewPermission, $managePermission]);
        $this->coachRole = Role::findOrCreate('Coach', 'web');
    }

    public function test_owner_can_view_coach_profile_and_edit_page(): void
    {
        $owner = $this->makeOwner();
        $coach = $this->makeCoach();

        $this->actingAs($owner)
            ->get(route('admin.coaches.view', $coach))
            ->assertOk()
            ->assertSee($coach->name)
            ->assertSee($coach->email)
            ->assertSee('Assigned role')
            ->assertSee('Coach');

        $this->actingAs($owner)
            ->get(route('admin.coaches.edit', $coach))
            ->assertOk()
            ->assertSee('Edit Coach')
            ->assertSee($coach->email)
            ->assertSee('Login Credentials')
            ->assertDontSee('Current password');
    }

    public function test_edit_updates_coach_and_allows_existing_email(): void
    {
        $owner = $this->makeOwner();
        $coach = $this->makeCoach();
        $originalPasswordHash = $coach->password;

        Livewire::actingAs($owner)
            ->test(Edit::class, ['coach' => $coach])
            ->set('name', 'Updated Coach Name')
            ->set('email', $coach->email)
            ->set('phone', '0771234567')
            ->call('updateCoach')
            ->assertRedirect(route('admin.coaches.view', ['coach' => $coach->id]));

        $this->assertDatabaseHas('users', [
            'id' => $coach->id,
            'name' => 'Updated Coach Name',
            'email' => $coach->email,
            'phone' => '0771234567',
        ]);
        $this->assertTrue($coach->fresh()->hasRole('Coach'));
        $this->assertSame($originalPasswordHash, $coach->fresh()->password);
    }

    public function test_edit_rejects_an_email_used_by_another_account(): void
    {
        $owner = $this->makeOwner();
        $coach = $this->makeCoach();
        $anotherCoach = $this->makeCoach();

        Livewire::actingAs($owner)
            ->test(Edit::class, ['coach' => $coach])
            ->set('email', $anotherCoach->email)
            ->call('updateCoach')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_owner_can_change_coach_login_email_and_audit_the_change(): void
    {
        $owner = $this->makeOwner();
        $coach = $this->makeCoach();
        $oldEmail = $coach->email;
        $newEmail = 'updated-coach@example.test';

        Livewire::actingAs($owner)
            ->test(Edit::class, ['coach' => $coach])
            ->set('email', $newEmail)
            ->set('phone', '0771234567')
            ->call('updateCoach')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.coaches.view', ['coach' => $coach->id]));

        $this->assertDatabaseHas('users', ['id' => $coach->id, 'email' => $newEmail]);
        $this->assertTrue($coach->fresh()->hasRole('Coach'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'email_changed',
            'module' => 'coaches',
            'related_id' => $coach->id,
        ]);

        $audit = AuditLog::where('related_id', $coach->id)->where('action', 'email_changed')->firstOrFail();
        $this->assertSame($oldEmail, $audit->previous_values['email']);
        $this->assertSame($newEmail, $audit->new_values['email']);
    }

    public function test_owner_can_reset_coach_password_preserving_role_permissions_and_auditing_no_credentials(): void
    {
        $owner = $this->makeOwner();
        $coach = $this->makeCoach();
        $permission = Permission::findOrCreate('training.record', 'web');
        $this->coachRole->givePermissionTo($permission);
        $oldHash = $coach->password;
        $plainPassword = 'Secure-Coach-Password-481!';
        config(['session.driver' => 'database']);

        DB::table('sessions')->insert([
            [
                'id' => 'coach-existing-session',
                'user_id' => $coach->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test coach session',
                'payload' => 'payload',
                'last_activity' => time(),
            ],
            [
                'id' => 'owner-existing-session',
                'user_id' => $owner->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test owner session',
                'payload' => 'payload',
                'last_activity' => time(),
            ],
        ]);

        Livewire::actingAs($owner)
            ->test(Edit::class, ['coach' => $coach])
            ->set('phone', '0771234567')
            ->set('password', $plainPassword)
            ->set('password_confirmation', $plainPassword)
            ->call('updateCoach')
            ->assertRedirect(route('admin.coaches.view', ['coach' => $coach->id]));

        $coach->refresh();
        $this->assertNotSame($oldHash, $coach->password);
        $this->assertTrue(Hash::check($plainPassword, $coach->password));
        $this->assertTrue($coach->hasRole('Coach'));
        $this->assertTrue($coach->hasPermissionTo('training.record'));
        $this->assertDatabaseMissing('sessions', ['id' => 'coach-existing-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'owner-existing-session']);

        $audit = AuditLog::where('related_id', $coach->id)->where('action', 'password_reset')->firstOrFail();
        $this->assertNull($audit->previous_values);
        $this->assertNull($audit->new_values);
        $this->assertStringNotContainsString($plainPassword, json_encode($audit->toArray()));
        $this->assertStringNotContainsString($coach->password, json_encode($audit->toArray()));
    }

    public function test_password_confirmation_must_match_before_reset(): void
    {
        $owner = $this->makeOwner();
        $coach = $this->makeCoach();
        $oldHash = $coach->password;

        Livewire::actingAs($owner)
            ->test(Edit::class, ['coach' => $coach])
            ->set('phone', '0771234567')
            ->set('password', 'New-Coach-Password-482!')
            ->set('password_confirmation', 'Different-Coach-Password-482!')
            ->call('updateCoach')
            ->assertHasErrors(['password' => 'confirmed']);

        $this->assertSame($oldHash, $coach->fresh()->password);
        $this->assertDatabaseMissing('audit_logs', [
            'related_id' => $coach->id,
            'action' => 'password_reset',
        ]);
    }

    public function test_empty_password_fields_preserve_existing_coach_password(): void
    {
        $owner = $this->makeOwner();
        $coach = $this->makeCoach();
        $oldHash = $coach->password;

        Livewire::actingAs($owner)
            ->test(Edit::class, ['coach' => $coach])
            ->set('name', 'Changed Name Only')
            ->set('phone', '0771234567')
            ->call('updateCoach')
            ->assertRedirect(route('admin.coaches.view', ['coach' => $coach->id]));

        $this->assertSame($oldHash, $coach->fresh()->password);
        $this->assertDatabaseMissing('audit_logs', [
            'related_id' => $coach->id,
            'action' => 'password_reset',
        ]);
    }

    public function test_owner_can_activate_and_deactivate_coach(): void
    {
        $owner = $this->makeOwner();
        $coach = $this->makeCoach();

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('toggleActive', $coach->id);

        $this->assertDatabaseHas('users', ['id' => $coach->id, 'is_active' => false]);
        $this->assertDatabaseHas('audit_logs', ['related_id' => $coach->id, 'action' => 'deactivated']);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('toggleActive', $coach->id);

        $this->assertDatabaseHas('users', ['id' => $coach->id, 'is_active' => true]);
        $this->assertDatabaseHas('audit_logs', ['related_id' => $coach->id, 'action' => 'activated']);
    }

    public function test_edit_prevents_the_signed_in_account_from_deactivating_itself(): void
    {
        $ownerCoach = User::factory()->create();
        $ownerCoach->assignRole([$this->ownerRole, $this->coachRole]);

        Livewire::actingAs($ownerCoach)
            ->test(Edit::class, ['coach' => $ownerCoach])
            ->set('phone', '0771234567')
            ->set('is_active', false)
            ->call('updateCoach')
            ->assertHasErrors(['is_active']);

        $this->assertDatabaseHas('users', ['id' => $ownerCoach->id, 'is_active' => true]);
    }

    public function test_list_action_prevents_self_deactivation(): void
    {
        $ownerCoach = User::factory()->create();
        $ownerCoach->assignRole([$this->ownerRole, $this->coachRole]);

        Livewire::actingAs($ownerCoach)
            ->test(Index::class)
            ->call('toggleActive', $ownerCoach->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $ownerCoach->id, 'is_active' => true]);
    }

    public function test_coach_cannot_access_admin_coach_routes_or_edit_an_owner_account(): void
    {
        $coach = $this->makeCoach();
        $owner = $this->makeOwner();

        $this->actingAs($coach)
            ->get(route('admin.coaches.index'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->get(route('admin.coaches.view', $owner))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('admin.coaches.edit', $owner))
            ->assertNotFound();
    }

    public function test_owner_without_coach_manage_permission_cannot_edit_credentials(): void
    {
        $this->ownerRole->revokePermissionTo('coaches.manage');
        $owner = User::factory()->create();
        $owner->assignRole($this->ownerRole);
        $coach = $this->makeCoach();

        $this->actingAs($owner)
            ->get(route('admin.coaches.edit', $coach))
            ->assertForbidden();

        Livewire::actingAs($owner)
            ->test(Edit::class, ['coach' => $coach])
            ->assertForbidden();
    }

    public function test_inactive_coach_cannot_log_in(): void
    {
        $coach = $this->makeCoach(['is_active' => false]);

        $this->post(route('login'), [
            'email' => $coach->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole($this->ownerRole);

        return $owner;
    }

    /** @param array<string, mixed> $attributes */
    private function makeCoach(array $attributes = []): User
    {
        $coach = User::factory()->create($attributes);
        $coach->assignRole($this->coachRole);

        return $coach;
    }
}

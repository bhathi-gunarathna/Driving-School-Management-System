<?php

namespace Tests\Feature;

use App\Livewire\Admin\Coaches\Edit;
use App\Livewire\Admin\Coaches\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertDontSee('New password');
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

    public function test_owner_can_activate_and_deactivate_coach(): void
    {
        $owner = $this->makeOwner();
        $coach = $this->makeCoach();

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('toggleActive', $coach->id);

        $this->assertDatabaseHas('users', ['id' => $coach->id, 'is_active' => false]);

        Livewire::actingAs($owner)
            ->test(Index::class)
            ->call('toggleActive', $coach->id);

        $this->assertDatabaseHas('users', ['id' => $coach->id, 'is_active' => true]);
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

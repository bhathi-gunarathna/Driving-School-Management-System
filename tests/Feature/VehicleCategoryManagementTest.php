<?php

namespace Tests\Feature;

use App\Livewire\Admin\VehicleCategories\Create;
use App\Livewire\Admin\VehicleCategories\Edit;
use App\Livewire\Admin\VehicleCategories\Index;
use App\Models\User;
use App\Models\VehicleCategory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VehicleCategoryManagementTest extends TestCase
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

    public function test_owner_can_create_and_view_a_vehicle_category(): void
    {
        $owner = $this->makeOwner();

        $component = Livewire::actingAs($owner)
            ->test(Create::class)
            ->set('category_code', 'b1')
            ->set('category_name', 'Motor Cars')
            ->set('description', 'Standard motor car course')
            ->set('training_hours', '12.5')
            ->set('price', '25000.00')
            ->set('is_active', true)
            ->call('createCategory');

        $category = VehicleCategory::query()->where('category_code', 'B1')->firstOrFail();

        $component->assertRedirect(route('admin.vehicle-categories.view', $category));

        $this->actingAs($owner)
            ->get(route('admin.vehicle-categories.view', $category))
            ->assertOk()
            ->assertSee('Motor Cars')
            ->assertSee('B1')
            ->assertSee('Standard motor car course')
            ->assertSee('Active')
            ->assertSee('12.50 hours');
    }

    public function test_category_creation_validates_unique_code_hours_and_price(): void
    {
        $owner = $this->makeOwner();
        $this->makeCategory(['category_code' => 'A1']);

        Livewire::actingAs($owner)
            ->test(Create::class)
            ->set('category_code', 'a1')
            ->set('category_name', 'Duplicate')
            ->set('training_hours', '0')
            ->set('price', '-5')
            ->call('createCategory')
            ->assertHasErrors([
                'category_code' => 'unique',
                'training_hours' => 'gt',
                'price' => 'min',
            ]);
    }

    public function test_owner_can_edit_category_and_keep_its_own_code(): void
    {
        $owner = $this->makeOwner();
        $category = $this->makeCategory(['category_code' => 'C1']);

        $component = Livewire::actingAs($owner)
            ->test(Edit::class, ['category' => $category])
            ->set('category_code', 'c1')
            ->set('category_name', 'Updated Cars')
            ->set('description', '')
            ->set('training_hours', '18')
            ->set('price', '0')
            ->set('is_active', false)
            ->call('updateCategory');

        $component->assertRedirect(route('admin.vehicle-categories.view', $category));
        $this->assertDatabaseHas('vehicle_categories', [
            'id' => $category->id,
            'category_code' => 'C1',
            'category_name' => 'Updated Cars',
            'training_hours' => 18,
            'price' => 0,
            'is_active' => false,
        ]);
    }

    public function test_edit_rejects_a_code_used_by_another_category(): void
    {
        $owner = $this->makeOwner();
        $category = $this->makeCategory(['category_code' => 'C1']);
        $otherCategory = $this->makeCategory(['category_code' => 'C2']);

        Livewire::actingAs($owner)
            ->test(Edit::class, ['category' => $category])
            ->set('category_code', $otherCategory->category_code)
            ->call('updateCategory')
            ->assertHasErrors(['category_code' => 'unique']);
    }

    public function test_category_search_filter_and_pagination_work(): void
    {
        $owner = $this->makeOwner();
        $this->makeCategory(['category_code' => 'MATCH', 'category_name' => 'Search Result']);
        $this->makeCategory(['category_code' => 'OFF', 'category_name' => 'Inactive Category', 'is_active' => false]);

        $component = Livewire::actingAs($owner)->test(Index::class);
        $component->set('search', 'MATCH')
            ->assertSee('Search Result')
            ->assertDontSee('Inactive Category');

        $component->set('search', '')
            ->set('statusFilter', 'inactive')
            ->assertSee('Inactive Category')
            ->assertDontSee('Search Result');

        for ($number = 1; $number <= 11; $number++) {
            $this->makeCategory([
                'category_code' => sprintf('P%02d', $number),
                'category_name' => sprintf('Page Category %02d', $number),
            ]);
        }

        $paginated = Livewire::actingAs($owner)->test(Index::class)
            ->call('gotoPage', 2)
            ->assertSee('P11')
            ->assertDontSee('P01');

        $paginated->set('search', 'P01')
            ->assertSee('P01')
            ->assertDontSee('P11');
    }

    public function test_owner_can_activate_and_deactivate_without_deleting_category(): void
    {
        $owner = $this->makeOwner();
        $category = $this->makeCategory();

        Livewire::actingAs($owner)->test(Index::class)->call('toggleActive', $category->id);

        $this->assertDatabaseHas('vehicle_categories', ['id' => $category->id, 'is_active' => false]);
        $this->assertSame(1, VehicleCategory::query()->count());
        $this->assertSame(0, VehicleCategory::active()->count());

        Livewire::actingAs($owner)->test(Index::class)->call('toggleActive', $category->id);

        $this->assertDatabaseHas('vehicle_categories', ['id' => $category->id, 'is_active' => true]);
    }

    public function test_coaches_and_owners_without_permissions_cannot_access_category_management(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole($this->coachRole);

        $ownerWithoutPermission = User::factory()->create();
        $ownerWithoutPermission->assignRole($this->ownerRole);
        $this->ownerRole->revokePermissionTo('vehicle-categories.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($coach)
            ->get(route('admin.vehicle-categories.index'))
            ->assertForbidden();

        $this->actingAs($ownerWithoutPermission)
            ->get(route('admin.vehicle-categories.index'))
            ->assertForbidden();
    }

    private function makeOwner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole($this->ownerRole);

        return $owner;
    }

    /** @param array<string, mixed> $attributes */
    private function makeCategory(array $attributes = []): VehicleCategory
    {
        return VehicleCategory::create(array_merge([
            'category_code' => 'B',
            'category_name' => 'Motor Cars',
            'description' => null,
            'training_hours' => 10,
            'price' => 1000,
            'is_active' => true,
        ], $attributes));
    }
}

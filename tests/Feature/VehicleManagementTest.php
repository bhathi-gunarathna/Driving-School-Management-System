<?php

namespace Tests\Feature;

use App\Livewire\Admin\Vehicles\Edit;
use App\Livewire\Admin\Vehicles\Index;
use App\Livewire\Admin\Vehicles\View;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDocumentRenewal;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VehicleManagementTest extends TestCase
{
    use RefreshDatabase;

    private Role $ownerRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $this->ownerRole = Role::findByName('Owner', 'web');
    }

    public function test_vehicle_with_renewal_history_cannot_be_deleted_and_history_remains_accessible(): void
    {
        $vehicle = $this->vehicle();
        $renewal = $this->renewal($vehicle);

        try {
            $vehicle->delete();
            $this->fail('A vehicle with renewal history was deleted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
        }

        $this->assertDatabaseHas('vehicle_document_renewals', [
            'id' => $renewal->id,
            'vehicle_id' => $vehicle->id,
            'previous_document_path' => 'vehicle-documents/'.$vehicle->id.'/old.pdf',
            'document_path' => 'vehicle-documents/'.$vehicle->id.'/current.pdf',
        ]);

        $owner = $this->owner();
        Livewire::actingAs($owner)->test(View::class, ['vehicle' => $vehicle])
            ->assertSee('Renewal History')
            ->assertSee('Insurance Renewed');
    }

    public function test_vehicle_status_changes_preserve_renewal_history(): void
    {
        $vehicle = $this->vehicle();
        $renewal = $this->renewal($vehicle);

        Livewire::actingAs($this->owner())->test(Index::class)
            ->call('setStatus', $vehicle->id, 'inactive');

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('vehicle_document_renewals', ['id' => $renewal->id, 'vehicle_id' => $vehicle->id]);
    }

    public function test_replacing_a_document_keeps_previous_file_and_writes_history(): void
    {
        Storage::fake('local');
        $vehicle = $this->vehicle([
            'insurance_expiry_date' => '2027-01-01',
            'insurance_document_path' => 'vehicle-documents/1/old-insurance.pdf',
        ]);
        $oldPath = 'vehicle-documents/'.$vehicle->id.'/old-insurance.pdf';
        $vehicle->update(['insurance_document_path' => $oldPath]);
        Storage::disk('local')->put($oldPath, 'previous document');

        Livewire::actingAs($this->owner())->test(Edit::class, ['vehicle' => $vehicle])
            ->set('insuranceDocument', UploadedFile::fake()->create('new-insurance.pdf', 20, 'application/pdf'))
            ->set('insurance_expiry_date', '2028-01-01')
            ->call('updateVehicle')
            ->assertHasNoErrors();

        $renewal = VehicleDocumentRenewal::query()->where('vehicle_id', $vehicle->id)->firstOrFail();
        $this->assertSame($oldPath, $renewal->previous_document_path);
        $this->assertSame('2027-01-01', $renewal->previous_expiry_date->toDateString());
        $this->assertTrue(Storage::disk('local')->exists($oldPath));
        $this->assertTrue(Storage::disk('local')->exists($renewal->document_path));
    }

    private function owner(): User
    {
        $owner = User::factory()->create();
        $owner->assignRole($this->ownerRole);

        return $owner;
    }

    private function vehicle(array $attributes = []): Vehicle
    {
        return Vehicle::query()->create(array_merge([
            'registration_number' => 'VH-'.str()->upper(str()->random(8)),
            'brand' => 'Test',
            'model' => 'Vehicle',
            'fuel_type' => 'petrol',
            'transmission_type' => 'manual',
            'current_mileage' => '100.0',
            'status' => 'active',
        ], $attributes));
    }

    private function renewal(Vehicle $vehicle): VehicleDocumentRenewal
    {
        return VehicleDocumentRenewal::query()->create([
            'vehicle_id' => $vehicle->id,
            'document_type' => 'insurance',
            'event_type' => 'renewed',
            'previous_expiry_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'previous_document_path' => 'vehicle-documents/'.$vehicle->id.'/old.pdf',
            'document_path' => 'vehicle-documents/'.$vehicle->id.'/current.pdf',
            'recorded_on' => '2026-01-01',
        ]);
    }
}

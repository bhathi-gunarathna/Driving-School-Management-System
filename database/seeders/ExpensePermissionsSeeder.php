<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ExpensePermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'expenses.view',
        'expenses.create',
        'expenses.edit',
        'expenses.manage_categories',
        'expenses.delete',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = collect(self::PERMISSIONS)->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        $owner = Role::query()->where('name', 'Owner')->where('guard_name', 'web')->firstOrFail();
        $owner->givePermissionTo($permissions->all());

        $coach = Role::query()->where('name', 'Coach')->where('guard_name', 'web')->first();
        if ($coach) {
            $coach->revokePermissionTo($permissions->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

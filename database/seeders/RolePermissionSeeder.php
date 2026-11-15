<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Dashboard
            'dashboard.view',

            // Students
            'students.view_all',
            'students.view_assigned',
            'students.create',
            'students.update',
            'students.delete',

            // Vehicle Categories
            'categories.view',
            'categories.manage',
            'vehicle-categories.view',
            'vehicle-categories.manage',

            // Payments
            'payments.view',
            'payments.manage',
            'payments.create',
            'payments.update',

            // Training
            'training.view_all',
            'training.view_assigned',
            'training.view',
            'training.manage',
            'training.record',
            'training.create',
            'training.update',
            'training.approve',

            // Trials
            'trials.view_all',
            'trials.view_assigned',
            'trials.view',
            'trials.manage',
            'trials.results.record',
            'trials.create',
            'trials.update',
            'trials.update_results',

            // Coaches
            'coaches.view',
            'coaches.manage',

            // Reports
            'reports.view_financial',
            'reports.view_operational',
            'reports.export',
            'financial-reports.view',
            'financial-reports.export',

            // Settings
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create Roles
        $owner = Role::findOrCreate('Owner', 'web');
        $coach = Role::findOrCreate('Coach', 'web');

        // Owner gets all permissions
        $owner->syncPermissions(
            Permission::where('guard_name', 'web')->get()
        );

        // Coach gets restricted permissions
        $coach->syncPermissions([
            'dashboard.view',
            'students.view_assigned',
            'training.view_assigned',
            'training.record',
            'trials.view_assigned',
            'trials.results.record',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

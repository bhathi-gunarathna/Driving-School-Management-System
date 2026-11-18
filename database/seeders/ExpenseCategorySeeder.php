<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach ([
            'Vehicle Repair & Maintenance',
            'Fuel Expenses',
            'Vehicle Service',
            'Spare Parts',
            'Insurance Payments',
            'Revenue License Payments',
            'Employee Expenses',
            'Petty Cash',
            'Office Expenses',
            'Other Expenses',
        ] as $name) {
            ExpenseCategory::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}

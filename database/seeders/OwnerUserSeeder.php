<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('OWNER_NAME');
        $email = env('OWNER_EMAIL');
        $password = env('OWNER_PASSWORD');

        if (!$name || !$email || !$password) {
            throw new \RuntimeException(
                'Please configure OWNER_NAME, OWNER_EMAIL and OWNER_PASSWORD in .env'
            );
        }

        $owner = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ]
        );

        $owner->syncRoles(['Owner']);
    }
}
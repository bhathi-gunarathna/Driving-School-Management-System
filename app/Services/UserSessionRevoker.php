<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Revokes sessions when the configured store supports targeted user deletion.
 * Database sessions are the only Laravel session driver with a portable,
 * per-user revocation operation.
 */
class UserSessionRevoker
{
    public function revoke(User|int|string $user): bool
    {
        if (config('session.driver') !== 'database') {
            return false;
        }

        $connection = config('session.connection')
            ? DB::connection(config('session.connection'))
            : DB::connection();
        $table = config('session.table', 'sessions');

        if (! $connection->getSchemaBuilder()->hasTable($table)) {
            return false;
        }

        $userId = $user instanceof User ? $user->getAuthIdentifier() : $user;

        $connection->table($table)->where('user_id', $userId)->delete();

        return true;
    }
}

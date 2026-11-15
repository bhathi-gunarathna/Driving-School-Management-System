<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_name', 'action', 'module', 'description', 'related_type', 'related_id',
        'previous_values', 'new_values', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return ['previous_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    }
}

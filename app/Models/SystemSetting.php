<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'school_name', 'logo_path', 'address', 'contact_number', 'email', 'website',
        'timezone', 'currency', 'date_format', 'receipt_prefix', 'student_number_prefix',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'school_name' => config('app.name', 'Driving School'),
            'timezone' => 'Asia/Colombo',
            'currency' => 'LKR',
            'date_format' => 'd M Y',
            'receipt_prefix' => 'DS-RCPT',
            'student_number_prefix' => 'DS',
        ]);
    }
}

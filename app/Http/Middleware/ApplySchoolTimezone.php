<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Throwable;

class ApplySchoolTimezone
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $settings = SystemSetting::query()->first(['timezone', 'currency', 'date_format']);
            $timezone = $settings?->timezone;
            if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
            $currency = $settings?->currency;
            if (is_string($currency) && preg_match('/^[A-Z]{3}$/', $currency)) {
                config(['school.currency' => $currency]);
            }
            $dateFormat = $settings?->date_format;
            if (is_string($dateFormat) && in_array($dateFormat, ['d M Y', 'Y-m-d', 'm/d/Y', 'd/m/Y'], true)) {
                config(['school.date_format' => $dateFormat]);
            }
        } catch (Throwable) {
            // Settings table may not exist during installation or before migrations.
        }

        return $next($request);
    }
}

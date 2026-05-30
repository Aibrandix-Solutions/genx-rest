<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Align Laravel / PHP default timezone with the active tenant (restaurant or shop)
 * so now(), DATE() boundaries, and naive order timestamps stay consistent.
 */
class SetApplicationTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $fallback = config('app.timezone', 'Asia/Colombo');
        $tz = $this->resolveValidTimezone($fallback);

        config(['app.timezone' => $tz]);
        date_default_timezone_set($tz);

        return $next($request);
    }

    private function resolveValidTimezone(string $fallback): string
    {
        if (!function_exists('timezone')) {
            return $fallback;
        }

        try {
            $candidate = timezone();
        } catch (\Throwable) {
            return $fallback;
        }

        if (!is_string($candidate) || $candidate === '') {
            return $fallback;
        }

        try {
            new \DateTimeZone($candidate);

            return $candidate;
        } catch (\Exception) {
            return $fallback;
        }
    }
}

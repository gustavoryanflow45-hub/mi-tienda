<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;

class GeolocationService
{
    public function countryCode(Request $request): ?string
    {
        $ip = $this->clientIp($request);

        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return config('app.env') === 'production' ? null : 'EC';
        }

        return Cache::remember("geo:{$ip}", now()->addHours(6), function () use ($ip) {
            $position = Location::get($ip);
            return $position->countryCode ?? null;
        });
    }

    public function gatewayFor(Request $request): string
    {
        return $this->countryCode($request) === 'EC' ? 'kushki' : 'stripe';
    }

    private function clientIp(Request $request): string
    {
        return $request->header('CF-Connecting-IP') ?? $request->ip();
    }
}

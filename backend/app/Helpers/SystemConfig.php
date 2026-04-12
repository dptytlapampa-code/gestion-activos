<?php

use App\Services\SystemSettingsService;
use Illuminate\Support\Facades\Cache;

if (! function_exists('system_config')) {
    function system_config(): object
    {
        return Cache::remember(
            system_config_cache_key(),
            now()->addHour(),
            static fn (): object => app(SystemSettingsService::class)->getCurrentSettings(),
        );
    }
}

if (! function_exists('system_config_cache_key')) {
    function system_config_cache_key(): string
    {
        return 'system_config.current';
    }
}

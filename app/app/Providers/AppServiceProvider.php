<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole() || ! $this->app->bound('request')) {
            return;
        }

        $request = request();
        $locale = config('app.locale');

        if ($request->user()?->language) {
            $locale = $request->user()->language;
        } elseif ($request->header('Accept-Language')) {
            $locale = $this->resolvePreferredLocale($request->header('Accept-Language'));
        }

        App::setLocale($locale);
    }

    private function resolvePreferredLocale(string $header): string
    {
        $supported = ['it', 'en'];
        $parts = explode(',', $header);

        foreach ($parts as $part) {
            $language = strtolower(substr(trim($part), 0, 2));

            if (in_array($language, $supported, true)) {
                return $language;
            }
        }

        return config('app.fallback_locale', config('app.locale'));
    }
}

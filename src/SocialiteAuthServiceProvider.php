<?php

namespace STS\SocialiteAuth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SocialiteAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     * @param \Illuminate\Http\Request $request
     */
    public function boot(Request $request)
    {
        $this->loadRoutesFrom(__DIR__ .'/../routes/web.php');
        $this->publishAssetsIfConsole();

        $this->registerSocialiteGuardMixin();
        $this->registerSocialiteAuthDriver();
        $this->addGuardToConfig();
        $this->configureRedirect();
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'socialite-auth');

        // Register the main class to use with the facade
        $this->app->singleton('socialite-auth', function () {
            return new SocialiteAuth();
        });
    }

    protected function publishAssetsIfConsole()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('socialite-auth.php'),
            ], 'config');
        }
    }

    protected function registerSocialiteGuardMixin()
    {
        SessionGuard::mixin(new GuardHelpers());
    }

    protected function registerSocialiteAuthDriver()
    {
        Auth::extend('socialite', function ($app, $name, array $config) {
            return Auth::createSessionDriver($name, $config);
        });
    }

    protected function addGuardToConfig()
    {
        if (!Config::has('auth.guards.socialite')) {
            Config::set('auth.guards.socialite', [
                'driver' => 'socialite',
                'provider' => 'users'
            ]);
        }
    }

    /**
     * If a redirect URL hasn't been configured, we deduce from the current request
     */
    protected function configureRedirect()
    {
        $providerName = config('socialite-auth.driver');

        if(!config("services.$providerName.redirect")) {
            config(["services.$providerName.redirect" => $this->app['request']->root() . "/socialite-auth/callback"]);
        }
    }
}
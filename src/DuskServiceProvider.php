<?php

namespace Laravel\Dusk;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\Http\ProxyServer;
use React\EventLoop\Loop;

class DuskServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ProxyServer::class, function($app) {
            return new ProxyServer(
                kernel: $app->make(HttpKernel::class),
                loop: Loop::get(),
            );
        });
    }

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if (! $this->app->environment('production')) {
            Route::group(array_filter([
                'prefix' => config('dusk.path', '_dusk'),
                'domain' => config('dusk.domain', null),
                'middleware' => config('dusk.middleware', 'web'),
            ]), function () {
                Route::get('/login/{userId}/{guard?}', [
                    'uses' => 'Laravel\Dusk\Http\Controllers\UserController@login',
                    'as' => 'dusk.login',
                ]);

                Route::get('/logout/{guard?}', [
                    'uses' => 'Laravel\Dusk\Http\Controllers\UserController@logout',
                    'as' => 'dusk.logout',
                ]);

                Route::get('/user/{guard?}', [
                    'uses' => 'Laravel\Dusk\Http\Controllers\UserController@user',
                    'as' => 'dusk.user',
                ]);
            });
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
                Console\DuskCommand::class,
                Console\DuskFailsCommand::class,
                Console\MakeCommand::class,
                Console\PageCommand::class,
                Console\PurgeCommand::class,
                Console\ComponentCommand::class,
                Console\ChromeDriverCommand::class,
            ]);
        }
    }
}

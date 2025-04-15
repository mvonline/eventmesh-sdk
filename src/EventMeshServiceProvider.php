<?php

namespace EventMesh\LaravelSdk;

use EventMesh\LaravelSdk\Commands\ListenCommand;
use EventMesh\LaravelSdk\Commands\PublishCommand;
use EventMesh\LaravelSdk\Commands\SagaStatusCommand;
use EventMesh\LaravelSdk\Saga\SagaManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class EventMeshServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/eventmesh.php', 'eventmesh'
        );

        $this->app->singleton(EventMeshManager::class, function ($app) {
            return new EventMeshManager($app['config']['eventmesh']);
        });

        $this->app->bind('eventmesh', function ($app) {
            return $app->make(EventMeshManager::class);
        });

        $this->app->singleton(SagaManager::class, function ($app) {
            return new SagaManager(
                $app->make(EventMeshManager::class),
                $app['config']['eventmesh']['saga'] ?? []
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/eventmesh.php' => config_path('eventmesh.php'),
        ], 'eventmesh-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishCommand::class,
                ListenCommand::class,
                SagaStatusCommand::class,
            ]);
        }
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('eventmesh.webhook.path'),
            'middleware' => config('eventmesh.webhook.middleware', ['api']),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/eventmesh.php');
        });
    }
} 
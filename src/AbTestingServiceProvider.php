<?php

namespace PeterVincze\AbTesting;

use Directory;
use GuzzleHttp\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use PeterVincze\AbTesting\Models\Experiment;
use PeterVincze\AbTesting\Models\Goal;
use PeterVincze\AbTesting\Commands\ResetCommand;
use PeterVincze\AbTesting\Commands\ReportCommand;
use PeterVincze\AbTesting\Commands\ConfigCommand;
use Illuminate\Filesystem\Filesystem;

class AbTestingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

        if ($this->app->runningInConsole()) {
            if (mb_strpos(__DIR__,'/ab-testing/')===false) {
                $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

                if (empty(array_diff($this->app->request->server('argv'),
                ["artisan","vendor:publish","--provider=PeterVincze\AbTesting\AbTestingServiceProvider"]))) {
                    $this->publishes([
                        dirname(__DIR__).'/config/config.php' => config_path('ab-testing.php'),
                        __DIR__.'/../public/abtestingproxy.php' => public_path('abtestingproxy.php')],
                    'config');
                    $baseLocal = new Filesystem;
                    if ($baseLocal->exists('public/.htaccess')) {
                        $baseLocal->delete('public/.htaccess');
                    }
                    if ($baseLocal->exists('server.php')) {
                        $baseLocal->delete('server.php');
                    }
                    $baseLocal->copy('vendor/peter-vincze/laravel-ab/public/.htaccess', 'public/.htaccess');
                    $baseLocal->copy('vendor/peter-vincze/laravel-ab/server.php', 'server.php');

                    $ConfigCommand = new ConfigCommand;
                    $ConfigCommand->handle();
                }
                $this->commands([
                    ConfigCommand::class,
                    ReportCommand::class,
                    ResetCommand::class,
                ]);
            }
        }

        $this->app->make('Illuminate\Contracts\Http\Kernel')->appendMiddlewareToGroup('web', 'PeterVincze\AbTesting\Middleware\AbTesting');
        Request::macro('abExperiment', function () {
            return app(AbTesting::class)->getExperiment();
        });

        Blade::if('ab', function ($experiment) {
            return app(AbTesting::class)->isExperiment($experiment);
        });
    }
    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        // Register the main class to use with the facade
        if ($this->app->runningInConsole()) {
            $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'ab-testing');
        }
        $this->app->singleton('ab-testing', function () {
            return new AbTesting;
        });
    }
}

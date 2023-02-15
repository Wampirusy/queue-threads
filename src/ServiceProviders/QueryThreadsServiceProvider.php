<?php

namespace ASW\QueueThreads\ServiceProviders;

use ASW\QueueThreads\Console\SetQueueThreads;
use Illuminate\Support\ServiceProvider;

class QueryThreadsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/queue.php', 'queue');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            if (function_exists('config_path')) {
                $this->publishes([
                    __DIR__.'/../../config/queue.php' => config_path('queue.php'),
                ], 'config');
            }
            $this->commands([SetQueueThreads::class]);
        }
    }
}

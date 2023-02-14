<?php
declare(strict_types=1);

namespace ASW\QueueThreads\ServiceProviders;

use ASW\QueueThreads\Console\SetQueueThreads;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class QueryThreadsServiceProvider extends ServiceProvider
{
    public function register()
    {
//        $this->mergeConfigFrom(__DIR__ . '/../../config/prometheus.php', 'prometheus');
//        $this->app->singleton(PrometheusExporterFactory::class, PrometheusExporterFactory::class);
//        $this->app->alias(PrometheusExporterFactory::class, PrometheusExporterFactoryInterface::class);
//        $this->app->singleton(StorageAdapterFactory::class, StorageAdapterFactory::class);
//        $this->app->alias(StorageAdapterFactory::class, StorageAdapterFactoryInterface::class);
//        $this->app->singleton(RenderTextFormat::class, RenderTextFormat::class);
//        $this->app->singleton(PrometheusExporter::class, static function (Application $app) {
//            /** @var PrometheusExporterFactoryInterface $factory */
//            $factory = $app->make(PrometheusExporterFactoryInterface::class);
//
//            return $factory->make();
//        });
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
//            if (function_exists('config_path')) {
//                $this->publishes([
//                    __DIR__ . '/../../config/prometheus.php' => config_path('prometheus.php'),
//                ], 'config');
//            }
            $this->commands([SetQueueThreads::class]);
        }

        $this->addRoutes();
    }

    /**
     * @throws BindingResolutionException
     */
    protected function addRoutes(): void
    {
//        /** @var Router $router */
//        $router = $this->app->make(Router::class);
//        /** @var Repository $config */
//        $config = $this->app->make(Repository::class);
//        $router->get($config->get('prometheus.route'), PrometheusController::class . '@metrics');
    }
}

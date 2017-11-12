<?php
/**
 * Date: 2017-11-12 15:26
 * @author: GROOT (pzyme@outlook.com)
 */

namespace Pgroot\SwaggerLite;
use Illuminate\Support\ServiceProvider;

class SwaggerLiteServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot() {
        $this->publishes([
            __DIR__.'/../../config/swagger-lite.php' => config_path('swagger-lite.php'),
            __DIR__.'/../../../public' => public_path('vendor/swagger-lite'),
            __DIR__.'/storage/swagger-lite' => storage_path('swagger-lite'),
        ]);

        $this->loadViewsFrom(__DIR__.'/../../views', 'swagger-lite');

        $this->publishes([
            __DIR__.'/../../views' => base_path('resources/views/vendor/swagger-lite'),
        ]);
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/swagger-lite.php', 'swagger-lite'
        );

        require_once __DIR__ .'/routes.php';
    }

}

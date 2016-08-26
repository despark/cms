<?php

namespace Despark\Cms\Providers;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Foundation\AliasLoader;
use File;
use Despark\Cms\Admin\Admin;
use Mailchimp;
use Spatie\Permission\Contracts\Permission;

class AdminServiceProvider extends ServiceProvider
{
    use AppNamespaceDetectorTrait;

    /**
     * The Artisan commands provided by starter kit.
     *
     * @var array
     */
    protected $commands = [
        'Despark\Cms\Console\Commands\AdminInstallCommand',
        'Despark\Cms\Console\Commands\AdminUpdateCommand',
        'Despark\Cms\Console\Commands\AdminUpdateProdCommand',
        'Despark\Cms\Console\Commands\AdminResourceCommand',
    ];

    /**
     * Bootstrap the application services.
     */
    public function boot(Router $router)
    {
        // Routes
        $router->group(['namespace' => 'Despark\Cms\Http\Controllers'], function ($router) {
            require __DIR__.'/../Http/routes.php';
        });

        $this->publishes([
            __DIR__.'/../Http/resourcesRoutes.php' => app_path('Http/resourcesRoutes.php'),
        ]);

        // We need to know the namespace of the running app.
        $router->group([
            'namespace' => $this->getAppNamespace().'Http\Controllers',
            'prefix' => 'admin',
            'middleware' => 'auth',
        ],
            function () {
                if (File::exists(app_path('Http/resourcesRoutes.php'))) {
                    require app_path('Http/resourcesRoutes.php');
                }
            });

        // Register Assets
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'views');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'lang');

        // Register the application commands
        $this->commands($this->commands);

        // Publish the Resources
        # Migrations
        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('/migrations'),
        ], 'migrations');

        # Seeders
        $this->publishes([
            __DIR__.'/../../database/seeds/' => database_path('/seeds'),
        ], 'seeds');

        # Configs
        $this->publishes([
            __DIR__.'/../../config/' => config_path(),
        ], 'config');
        $this->publishes([
            __DIR__.'/../../resources/' => base_path('/resources'),
        ], 'resources');
        $this->publishes([
            __DIR__.'/../../gulp/' => base_path('/gulp'),
        ], 'gulp');
        $this->publishes([
            __DIR__.'/../../public/' => public_path(),
        ], 'public');
        $this->publishes([
            __DIR__.'/../../app/' => base_path('/app'),
        ], 'gulp');

        $this->publishes([
            __DIR__.'/../../.env.example' => base_path('.env.example'),
            __DIR__.'/../../package.json' => base_path('package.json'),
            __DIR__.'/../../bower.json' => base_path('bower.json'),
            __DIR__.'/../../.bowerrc' => base_path('.bowerrc'),
            __DIR__.'/../../gulpfile.js' => base_path('gulpfile.js'),
        ]);

        $configPaths = config('admin.bootstrap.paths');
        if ($configPaths) {
            foreach ($configPaths as $key => $path) {
                if ( ! is_dir($path)) {
                    mkdir($path, 775, true);
                }
            }
        }

        exec('composer dumpautoload');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        /*
         * Register the service provider for the dependency.
         */
        $this->app->register('Conner\Tagging\Providers\TaggingServiceProvider');
        $this->app->register('Collective\Html\HtmlServiceProvider');
        $this->app->register('Intervention\Image\ImageServiceProvider');
        $this->app->register('Cviebrock\EloquentSluggable\SluggableServiceProvider');
        $this->app->register('Roumen\Sitemap\SitemapServiceProvider');
        $this->app->register('Rutorika\Sortable\SortableServiceProvider');
        $this->app->register('Jenssegers\Agent\AgentServiceProvider');
        $this->app->register('Spatie\Permission\PermissionServiceProvider');
        $this->app->register('Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider');

        /*
         * Create aliases for the dependency.
         */
        $loader = AliasLoader::getInstance();
        $loader->alias('Form', 'Collective\Html\FormFacade');
        $loader->alias('Html', 'Collective\Html\HtmlFacade');
        $loader->alias('Image', 'Intervention\Image\Facades\Image');
        $loader->alias('Agent', 'Jenssegers\Agent\Facades\Agent');

        /*
         * Manually register Mailchimp
         */
        $this->app->singleton('Mailchimp', function ($app) {
            $config = $app['config']['mailchimp'];

            return new Mailchimp($config['apikey']);
        });

        /*
         * Swap Permission model implementation
         */
        $this->app->bind(Permission::class, \Despark\Cms\Models\Permission::class);
    }
}

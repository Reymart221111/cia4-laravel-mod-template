<?php

namespace App\Config;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;
use Illuminate\Hashing\HashManager;
use Illuminate\Pagination\Paginator;

class Eloquent
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Capsule
     */
    protected $capsule;

    public function __construct()
    {
        $this->setupDatabaseConnection();
        $this->setupContainer();
        $this->registerServices();
    }

    /**
     * Configure and initialize the database connection
     */
    protected function setupDatabaseConnection(): void
    {
        $this->capsule = new Capsule;

        $this->capsule->addConnection([
            'host'      => env('database.default.hostname', 'localhost'),
            'driver'    => env('database.default.DBDriver', 'mysql'),
            'database'  => env('database.default.database', 'ci4'),
            'username'  => env('database.default.username', 'root'),
            'password'  => env('database.default.password', ''),
            'charset'   => env('database.default.DBCharset', 'utf8'),
            'collation' => env('database.default.DBCollat', 'utf8_general_ci'),
            'prefix'    => env('database.default.DBPrefix', ''),
        ]);

        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    /**
     * Configure pagination once for the entire application
     */
    protected function configurePagination(): void
    {
        // Register a singleton instance of some pagination components
        $this->container->singleton('paginator.currentPage', function () {
            return isset($_GET['page']) ? (int) $_GET['page'] : 1;
        });

        $this->container->singleton('paginator.currentPath', function () {
            return current_url();
        });

        $paginator = Paginator::class;

        // Set the global resolvers to use our container
        $paginator::currentPageResolver(function () {
            return $this->container->make('paginator.currentPage');
        });

        $paginator::currentPathResolver(function () {
            return $this->container->make('paginator.currentPath');
        });

        // Use Bootstrap styling for pagination
        $paginator::useBootstrap();
    }

    /**
     * Initialize the container and set it as the Facade application root
     */
    protected function setupContainer(): void
    {
        $this->container = new Container();
        Facade::setFacadeApplication($this->container);
    }

    /**
     * Register required services in the container
     */
    protected function registerServices(): void
    {
        $this->registerConfigService();
        $this->registerHashService();
        $this->configurePagination();
    }

    /**
     * Register the configuration repository
     */
    protected function registerConfigService(): void
    {
        $this->container->singleton('config', function () {
            return new Repository([
                'hashing' => [
                    'driver' => 'bcrypt',
                    'bcrypt' => [
                        'rounds' => 10,
                    ],
                ]
            ]);
        });
    }

    /**
     * Register the hash manager service
     */
    protected function registerHashService(): void
    {
        $this->container->singleton('hash', function ($app) {
            return new HashManager($app);
        });
    }
}

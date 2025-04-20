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

        $this->setPageResolver();
        $this->setPathResolver();
        $this->setPaginationStyling();


        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    /**
     * Set the correct page resolver
     */
    protected function setPageResolver(): void
    {
        Paginator::currentPageResolver(fn() => isset($_GET['page']) ? (int) $_GET['page'] : 1);
    }

    /**
     * Set the path resolver to use current URL
     */
    protected function setPathResolver(): void
    {
        Paginator::currentPathResolver(fn() => current_url());
    }

    /**
     * Use Bootstrap styling for pagination
     */
    protected function setPaginationStyling(): void
    {
        Paginator::useBootstrap();
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

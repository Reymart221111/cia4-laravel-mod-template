<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;

class LaravelMigrate extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'laravel-migrate';
    protected $description = 'Runs Laravel migrations in CodeIgniter 4';
    protected $usage       = 'laravel-migrate [up|down|refresh|status]';
    protected $arguments   = [
        'action' => 'The action to perform: up, down, refresh, or status (default: up)',
    ];
    protected $options    = [];
    protected $capsule;
    protected $repository;
    protected $migrator;
    protected $migrationPath;

    /**
     * Execute the command
     */
    public function run(array $params)
    {
        try {
            $this->setupEnvironment();

            $action = $params[0] ?? 'up';
            $this->executeAction($action);
        } catch (\Exception $e) {
            CLI::error("Error executing migration command: " . $e->getMessage());
        }
    }

    /**
     * Setup all required dependencies
     */
    private function setupEnvironment()
    {
        $this->setupDatabase();
        $this->setupRepository();
        $this->setupMigrator();
    }

    /**
     * Initialize database connection
     */
    private function setupDatabase()
    {
        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'host'      => env('database.default.hostname'),
            'driver'    => env('database.default.DBDriver'),
            'database'  => env('database.default.database'),
            'username'  => env('database.default.username'),
            'password'  => env('database.default.password'),
            'charset'   => env('database.default.DBCharset', 'utf8'),
            'collation' => env('database.default.DBCollat', 'utf8_general_ci'),
            'prefix'    => env('database.default.DBPrefix', ''),
            'port'      => env('database.default.port'),
        ]);

        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        $this->setUpFacadeContainer();
    }

    private function setUpFacadeContainer()
    {
        $container = $this->capsule->getContainer();
        Facade::setFacadeApplication($container);

        $container->instance('db', $this->capsule->getDatabaseManager());

        $container->bind('db.schema', function ($container) {
            return $container['db']->connection()->getSchemaBuilder();
        });
    }

    /**
     * Initialize migration repository
     */
    private function setupRepository()
    {
        $this->repository = new DatabaseMigrationRepository(
            $this->capsule->getDatabaseManager(),
            'migrations'
        );

        if (!$this->repository->repositoryExists()) {
            $this->repository->createRepository();
            CLI::write('Laravel migration repository created.', 'green');
        }
    }

    /**
     * Initialize migration manager
     */
    private function setupMigrator()
    {
        $this->migrationPath = APPPATH . 'Database/Laravel-Migrations';
        $filesystem = new Filesystem();
        $this->migrator = new Migrator(
            $this->repository,
            $this->capsule->getDatabaseManager(),
            $filesystem
        );
        $this->migrator->setConnection('default');
    }

    /**
     * Execute the selected action
     */
    private function executeAction(string $action)
    {
        switch ($action) {
            case 'up':
                $this->handleUpAction();
                break;
            case 'down':
                $this->handleDownAction();
                break;
            case 'refresh':
                $this->handleRefreshAction();
                break;
            case 'status':
                $this->handleStatusAction();
                break;
            default:
                $this->showUsage();
                break;
        }
    }

    /**
     * Handle migration up action
     */
    private function handleUpAction()
    {
        $before = $this->repository->getRan();
        $this->migrator->run($this->migrationPath);
        $after = $this->repository->getRan();
        $migrated = array_diff($after, $before);

        $this->showUpResult($migrated);
    }

    /**
     * Handle migration down action
     */
    private function handleDownAction()
    {
        $before = $this->repository->getRan();
        $this->migrator->rollback($this->migrationPath);
        $after = $this->repository->getRan();
        $rolledBack = array_diff($before, $after);

        $this->showDownResult($rolledBack);
    }

    /**
     * Handle migration refresh action
     */
    private function handleRefreshAction()
    {
        $this->migrator->reset($this->migrationPath);
        $this->migrator->run($this->migrationPath);

        $this->showRefreshResult();
    }

    /**
     * Handle migration status action
     */
    private function handleStatusAction()
    {
        $status = $this->getMigrationStatus();
        $this->showStatusResult($status);
    }

    /**
     * Get migration status information
     */
    private function getMigrationStatus(): array
    {
        $ran = $this->repository->getRan();
        $files = $this->migrator->getMigrationFiles($this->migrationPath);

        $status = [];
        foreach ($files as $file => $name) {
            $status[$name] = in_array($name, $ran) ? 'Ran' : 'Pending';
        }

        return $status;
    }

    /**
     * Show migration up results
     */
    private function showUpResult(array $migrations): void
    {
        if (empty($migrations)) {
            CLI::write('Nothing to migrate.', 'green');
        } else {
            CLI::write('Laravel migrations ran successfully.', 'green');
            foreach ($migrations as $migration) {
                CLI::write("Migrated: {$migration}");
            }
        }
    }

    /**
     * Show migration down results
     */
    private function showDownResult(array $migrations): void
    {
        if (empty($migrations)) {
            CLI::write('Nothing to rollback.', 'green');
        } else {
            CLI::write('Laravel migrations rolled back successfully.', 'green');
            foreach ($migrations as $migration) {
                CLI::write("Rolled back: {$migration}");
            }
        }
    }

    /**
     * Show migration refresh results
     */
    private function showRefreshResult(): void
    {
        CLI::write('All migrations rolled back and re-run successfully.', 'green');
    }

    /**
     * Show migration status results
     */
    private function showStatusResult(array $status): void
    {
        CLI::write('Laravel Migration Status:', 'yellow');
        CLI::write('-----------------', 'yellow');

        foreach ($status as $name => $state) {
            CLI::write("{$name}: {$state}");
        }
    }

    /**
     * Show usage information
     */
    private function showUsage(): void
    {
        CLI::write('Usage: php spark laravel-migrate [up|down|refresh|status]', 'yellow');
        CLI::write('  up     : Run all pending Laravel migrations');
        CLI::write('  down   : Roll back the last batch of Laravel migrations');
        CLI::write('  refresh: Roll back and re-run all Laravel migrations');
        CLI::write('  status : Show the status of Laravel migrations');
    }
}

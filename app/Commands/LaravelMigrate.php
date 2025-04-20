<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use PDO;
use PDOException;

class LaravelMigrate extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'laravel-migrate';
    protected $description = 'Runs Laravel migrations in CodeIgniter 4';
    protected $usage       = 'laravel-migrate [up|down|refresh|status|fresh]';
    protected $arguments   = [
        'action' => 'The action to perform: up, down, refresh, status, or fresh (default: up)',
    ];
    protected $options    = [];
    protected $capsule;
    protected $repository;
    protected $migrator;
    protected $migrationPath;
    protected $dbConfig = [];

    /**
     * Execute the command
     */
    public function run(array $params)
    {
        try {
            // Load database configuration first
            $this->loadDatabaseConfig();

            // Check if database exists before proceeding
            if (!$this->checkDatabaseExists()) {
                $this->promptAndCreateDatabase();
            }

            $this->setupEnvironment();

            $action = $params[0] ?? 'up';
            $this->executeAction($action);
        } catch (\Exception $e) {
            CLI::error("Error executing migration command: " . $e->getMessage());
        }
    }

    /**
     * Load database configuration from environment
     */
    private function loadDatabaseConfig()
    {
        $this->dbConfig = [
            'host'      => env('database.default.hostname'),
            'driver'    => env('database.default.DBDriver'),
            'database'  => env('database.default.database'),
            'username'  => env('database.default.username'),
            'password'  => env('database.default.password'),
            'charset'   => env('database.default.DBCharset', 'utf8'),
            'collation' => env('database.default.DBCollat', 'utf8_general_ci'),
            'prefix'    => env('database.default.DBPrefix', ''),
            'port'      => env('database.default.port'),
        ];
    }

    /**
     * Check if the specified database exists
     */
    private function checkDatabaseExists(): bool
    {
        try {
            // Create DSN without database name
            $driver = strtolower($this->dbConfig['driver']);

            return match ($driver) {
                'mysql', 'mariadb' => $this->checkMysqlDatabaseExists(),
                'pgsql' => $this->checkPgsqlDatabaseExists(),
                'sqlite' => file_exists($this->dbConfig['database']),
                'sqlsrv' => $this->checkSqlsrvDatabaseExists(),
                default => $this->handleUnsupportedDriver($driver, 'checking')
            };
        } catch (PDOException $e) {
            CLI::error("Database connection error: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Prompt user and create database if confirmed
     */
    private function promptAndCreateDatabase()
    {
        CLI::write("Database '{$this->dbConfig['database']}' does not exist.", 'yellow');
        $confirm = CLI::prompt('Would you like to create it?', ['y', 'n']);

        if ($confirm === 'y') {
            $this->createDatabase();
        } else {
            CLI::error('Database is required to continue. Aborting.');
            exit(1);
        }
    }

    /**
     * Check if MySQL/MariaDB database exists
     */
    private function checkMysqlDatabaseExists(): bool
    {
        $dsn = "mysql:host={$this->dbConfig['host']};port={$this->dbConfig['port']}";
        $pdo = new PDO($dsn, $this->dbConfig['username'], $this->dbConfig['password']);
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->dbConfig['database']}'");
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if PostgreSQL database exists
     */
    private function checkPgsqlDatabaseExists(): bool
    {
        $dsn = "pgsql:host={$this->dbConfig['host']};port={$this->dbConfig['port']}";
        $pdo = new PDO($dsn, $this->dbConfig['username'], $this->dbConfig['password']);
        $stmt = $pdo->query("SELECT datname FROM pg_database WHERE datname = '{$this->dbConfig['database']}'");
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if SQL Server database exists
     */
    private function checkSqlsrvDatabaseExists(): bool
    {
        $dsn = "sqlsrv:Server={$this->dbConfig['host']},{$this->dbConfig['port']}";
        $pdo = new PDO($dsn, $this->dbConfig['username'], $this->dbConfig['password']);
        $stmt = $pdo->query("SELECT name FROM sys.databases WHERE name = '{$this->dbConfig['database']}'");
        return $stmt->rowCount() > 0;
    }

    /**
     * Handle unsupported driver
     */
    private function handleUnsupportedDriver(string $driver, string $operation): bool
    {
        CLI::write("Warning: Auto-{$operation} not supported for '{$driver}'. Assuming database exists.", 'yellow');
        return true;
    }

    /**
     * Create the database
     */
    private function createDatabase()
    {
        try {
            $driver = strtolower($this->dbConfig['driver']);
            $database = $this->dbConfig['database'];

            match ($driver) {
                'mysql', 'mariadb' => $this->createMysqlDatabase(),
                'pgsql' => $this->createPgsqlDatabase(),
                'sqlite' => $this->createSqliteDatabase(),
                'sqlsrv' => $this->createSqlsrvDatabase(),
                default => throw new \Exception("Database driver '{$driver}' is not supported for auto-creation.")
            };

            CLI::write("Database '$database' created successfully.", 'green');
        } catch (PDOException | \Exception $e) {
            CLI::error("Failed to create database: " . $e->getMessage());
            exit(1);
        }
    }

    /**
     * Create MySQL/MariaDB database
     */
    private function createMysqlDatabase(): void
    {
        $dsn = "mysql:host={$this->dbConfig['host']};port={$this->dbConfig['port']}";
        $pdo = new PDO($dsn, $this->dbConfig['username'], $this->dbConfig['password']);

        // Create database with proper character set and collation
        $charset = $this->dbConfig['charset'];
        $collation = $this->dbConfig['collation'];
        $database = $this->dbConfig['database'];
        $pdo->exec("CREATE DATABASE `$database` CHARACTER SET $charset COLLATE $collation");
    }

    /**
     * Create PostgreSQL database
     */
    private function createPgsqlDatabase(): void
    {
        $dsn = "pgsql:host={$this->dbConfig['host']};port={$this->dbConfig['port']}";
        $pdo = new PDO($dsn, $this->dbConfig['username'], $this->dbConfig['password']);
        $database = $this->dbConfig['database'];
        $pdo->exec("CREATE DATABASE \"$database\"");

        // Set encoding if available
        if (!empty($this->dbConfig['charset'])) {
            $charset = $this->dbConfig['charset'];
            $pdo->exec("ALTER DATABASE \"$database\" SET client_encoding TO '$charset'");
        }
    }

    /**
     * Create SQLite database
     */
    private function createSqliteDatabase(): void
    {
        $database = $this->dbConfig['database'];
        $directory = dirname($database);

        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Create empty SQLite file
        file_put_contents($database, '');
        chmod($database, 0644);
    }

    /**
     * Create SQL Server database
     */
    private function createSqlsrvDatabase(): void
    {
        $dsn = "sqlsrv:Server={$this->dbConfig['host']},{$this->dbConfig['port']}";
        $pdo = new PDO($dsn, $this->dbConfig['username'], $this->dbConfig['password']);
        $database = $this->dbConfig['database'];
        $pdo->exec("CREATE DATABASE [$database]");

        // Set collation if specified
        if (!empty($this->dbConfig['collation'])) {
            $collation = $this->dbConfig['collation'];
            $pdo->exec("ALTER DATABASE [$database] COLLATE $collation");
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
        $this->capsule->addConnection($this->dbConfig);

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
            case 'fresh':
                $this->handleFreshAction();
                break;
            default:
                $this->showUsage();
                break;
        }
    }

    private function handleFreshAction()
    {
        CLI::write('Dropping all tables...', 'yellow');
        $this->dropAllTables();

        // Recreate migrations table
        CLI::write('Recreating migrations table...', 'yellow');
        $this->setupRepository();

        CLI::write('Running all migrations...', 'yellow');
        $this->handleUpAction();
        CLI::write('Database has been freshened.', 'green');
    }

    /**
     * Drop all database tables
     */
    private function dropAllTables()
    {
        $connection = $this->capsule->getConnection();
        $schema = $connection->getSchemaBuilder();
        $driver = $connection->getDriverName();

        // Disable foreign key checks
        if ($driver === 'mysql') {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            $connection->statement('PRAGMA foreign_keys = OFF;');
        }

        // Drop all tables
        $schema->dropAllTables();

        // Re-enable foreign key checks
        if ($driver === 'mysql') {
            $connection->statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            $connection->statement('PRAGMA foreign_keys = ON;');
        }

        CLI::write('Dropped all tables successfully.', 'green');
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
        CLI::write('Usage: php spark laravel-migrate [up|down|refresh|status|fresh]', 'yellow');
        CLI::write('  up     : Run all pending Laravel migrations');
        CLI::write('  down   : Roll back the last batch of Laravel migrations');
        CLI::write('  refresh: Roll back and re-run all Laravel migrations');
        CLI::write('  status : Show the status of Laravel migrations');
        CLI::write('  fresh  : Drop all tables and re-run all Laravel migrations');
    }
}

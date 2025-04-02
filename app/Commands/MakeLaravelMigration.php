<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

/**
 * Laravel Migration Generator Command
 * 
 * Creates Laravel-style migration files for CodeIgniter projects
 */
class MakeLaravelMigration extends BaseCommand
{
    /**
     * The group the command is lumped under
     * 
     * @var string
     */
    protected $group = 'Database';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'make:laravel-migration';

    /**
     * The Command's description
     *
     * @var string
     */
    protected $description = 'Create a new Laravel-style migration file';

    /**
     * The Command's usage
     *
     * @var string
     */
    protected $usage = 'make:laravel-migration <name> [options]';

    /**
     * The Command's arguments
     *
     * @var array
     */
    protected $arguments = [
        'name' => 'The migration name (e.g., "CreatePostsTable" or "AddImageToPosts")'
    ];

    /**
     * The Command's options
     *
     * @var array
     */
    protected $options = [
        'table' => 'Table name to modify (required for alter migrations)'
    ];

    /**
     * Path where migration files will be stored
     *
     * @var string
     */
    protected $migrationPath = APPPATH . 'Database/Laravel-Migrations/';

    /**
     * Runs the command
     *
     * @param array $params Command parameters
     * @return void
     */
    public function run(array $params)
    {
        $name = $params[0] ?? null;
    
        if (empty($name)) {
            CLI::error('You must provide a migration name.');
            return;
        }
    
        $tableName = $this->extractTableOptionFromArguments();
        CLI::write("Table option detected: " . ($tableName ?? 'none'), 'yellow');
        
        $this->createMigration($name, $tableName);
    }
    
    /**
     * Extracts table name from command line arguments
     *
     * @return string|null The table name if found, null otherwise
     */
    protected function extractTableOptionFromArguments(): ?string
    {
        global $argv;
        $tableName = null;
        
        foreach ($argv as $arg) {
            if (strpos($arg, '--table=') === 0) {
                $tableName = substr($arg, 8);
                break;
            }
        }
        
        return $tableName;
    }
    
    /**
     * Creates a migration file
     *
     * @param string $migrationName The name of the migration
     * @param string|null $tableName The table name for modifications
     * @return void
     */
    protected function createMigration(string $migrationName, ?string $tableName = null)
    {
        $snakeCaseName = $this->toSnakeCase($migrationName);
        $fileName = $this->generateFileName($snakeCaseName);
        $filePath = $this->migrationPath . $fileName;
    
        if (!$this->validateMigrationDoesNotExist($filePath, $fileName)) {
            return;
        }
    
        $this->ensureMigrationDirectoryExists();
    
        CLI::write("Inside createMigration - Table option: " . ($tableName ?? 'none'), 'yellow');
        
        if ($tableName !== null && $tableName !== '') {
            CLI::write("Generating modify migration for table: {$tableName}", 'yellow');
            $code = $this->generateModifyMigrationCode($tableName);
        } else {
            $tableName = $this->getTableNameFromMigration($migrationName);
            CLI::write("Generating create migration for table: {$tableName}", 'yellow');
            $code = $this->generateCreateMigrationCode($tableName);
        }
    
        $this->writeMigrationFile($filePath, $fileName, $code);
    }

    /**
     * Validates that the model exists for the given table
     *
     * @param string $tableName The table name
     * @return void
     * @throws \RuntimeException If model does not exist
     */
    private function validateModelExists(string $tableName): void
    {
        $modelName = $this->getModelNameFromTable($tableName);
        $modelPath = APPPATH . 'Models/' . $modelName . '.php';

        if (!file_exists($modelPath)) {
            throw new \RuntimeException("Model {$modelName} does not exist in app/Models/");
        }

        $class = "App\\Models\\{$modelName}";
        if (!class_exists($class)) {
            throw new \RuntimeException("Model class {$modelName} not found in {$modelPath}");
        }
    }

    /**
     * Converts a table name to a model name
     *
     * @param string $tableName The table name
     * @return string The model name
     */
    private function getModelNameFromTable(string $tableName): string
    {
        $inflector = Services::inflector();
        $singular = $inflector->singularize($tableName);
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $singular)));
    }

    /**
     * Generates migration code for creating a new table
     *
     * @param string $tableName The table name
     * @return string The migration code
     */
    private function generateCreateMigrationCode(string $tableName): string
    {
        return <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};

EOT;
    }

    /**
     * Generates migration code for modifying an existing table
     *
     * @param string $tableName The table name
     * @return string The migration code
     */
    private function generateModifyMigrationCode(string $tableName): string
    {
        return <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            // Add columns or modifications here
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            // Reverse modifications here
        });
    }
};

EOT;
    }

    /**
     * Extracts table name from the migration name
     *
     * @param string $migrationName The migration name
     * @return string The extracted table name
     */
    protected function getTableNameFromMigration(string $migrationName): string
    {
        $snakeCase = $this->toSnakeCase($migrationName);
        $parts = explode('_', $snakeCase);
        return $this->extractTableNameFromParts($parts);
    }

    /**
     * Extracts table name from parts of the migration name
     *
     * @param array $parts Parts of the migration name
     * @return string The extracted table name
     */
    private function extractTableNameFromParts(array $parts): string
    {
        if ($this->isCreateTableMigration($parts)) {
            return implode('_', array_slice($parts, 1, -1));
        }
        return implode('_', $parts);
    }

    /**
     * Checks if the migration is for creating a table
     *
     * @param array $parts Parts of the migration name
     * @return bool True if it's a create table migration
     */
    private function isCreateTableMigration(array $parts): bool
    {
        return $parts[0] === 'create' && end($parts) === 'table';
    }

    /**
     * Converts a string to snake_case
     *
     * @param string $input The input string
     * @return string The snake_case string
     */
    protected function toSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '_$1', $input));
    }

    /**
     * Generates a filename for the migration
     *
     * @param string $snakeCaseName The snake_case name
     * @return string The generated filename
     */
    private function generateFileName(string $snakeCaseName): string
    {
        $timestamp = date('Y_m_d_His');
        return "{$timestamp}_{$snakeCaseName}.php";
    }

    /**
     * Validates that the migration does not exist
     *
     * @param string $filePath The file path
     * @param string $fileName The file name
     * @return bool True if the migration does not exist
     */
    private function validateMigrationDoesNotExist(string $filePath, string $fileName): bool
    {
        if (file_exists($filePath)) {
            CLI::error("Migration {$fileName} already exists.");
            return false;
        }
        return true;
    }

    /**
     * Ensures the migration directory exists
     *
     * @return void
     */
    private function ensureMigrationDirectoryExists(): void
    {
        if (!is_dir($this->migrationPath)) {
            mkdir($this->migrationPath, 0777, true);
        }
    }

    /**
     * Writes the migration file
     *
     * @param string $filePath The file path
     * @param string $fileName The file name
     * @param string $code The migration code
     * @return void
     */
    private function writeMigrationFile(string $filePath, string $fileName, string $code): void
    {
        if (write_file($filePath, $code)) {
            CLI::write("Migration created: {$fileName}", 'green');
        } else {
            CLI::error("Error creating migration: {$fileName}");
        }
    }
}
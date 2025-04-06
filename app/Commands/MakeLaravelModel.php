<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Laravel Model Generator Command
 * 
 * Creates Laravel-style Eloquent model files for CodeIgniter projects
 * with optional migration generation
 */
class MakeLaravelModel extends BaseCommand
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
    protected $name = 'make:laravel-model';

    /**
     * The Command's description
     *
     * @var string
     */
    protected $description = 'Create a new Laravel-style Eloquent model with optional migration';

    /**
     * The Command's usage
     *
     * @var string
     */
    protected $usage = 'make:laravel-model <name> [-m]';

    /**
     * The Command's arguments
     *
     * @var array
     */
    protected $arguments = [
        'name' => 'The model class name'
    ];

    /**
     * The Command's options
     *
     * @var array
     */
    protected $options = [
        '-m' => 'Create a new migration file for the model'
    ];

    /**
     * Path where migration files will be stored
     *
     * @var string
     */
    protected $migrationPath = APPPATH . 'Database/Laravel-Migrations/';

    /**
     * Path where model files will be stored
     *
     * @var string
     */
    protected $modelPath = APPPATH . 'Models/';

    /**
     * Runs the command
     *
     * @param array $params Command parameters
     * @return void
     */
    public function run(array $params)
    {
        $modelName = $params[0] ?? null;

        if (empty($modelName)) {
            CLI::error('You must provide a model name.');
            return;
        }

        $modelCreated = $this->createModel($modelName);

        if ($modelCreated && CLI::getOption('m')) {
            $this->createMigration($modelName);
        }
    }

    /**
     * Creates a model file
     *
     * @param string $modelName The model class name
     * @return bool True if model was created successfully
     */
    protected function createModel(string $modelName): bool
    {
        $filePath = $this->modelPath . $modelName . '.php';

        if (file_exists($filePath)) {
            CLI::error("Model {$modelName} already exists.");
            return false;
        }

        $code = $this->generateModelCode($modelName);

        if (!$this->ensureDirectoryExists($this->modelPath)) {
            return false;
        }

        if (!write_file($filePath, $code)) {
            CLI::error("Error creating model: {$modelName}.php");
            return false;
        }

        CLI::write("Model created: {$modelName}.php", 'green');
        return true;
    }

    /**
     * Creates a migration file for the model
     *
     * @param string $modelName The model class name
     * @return bool True if migration was created successfully
     */
    protected function createMigration(string $modelName): bool
    {
        $tableName = $this->getTableName($modelName);
        $migrationName = "create_{$tableName}_table";
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$migrationName}.php";
        $filePath = $this->migrationPath . $fileName;

        if (file_exists($filePath)) {
            CLI::error("Migration {$fileName} already exists.");
            return false;
        }

        $code = $this->generateMigrationCode($tableName);

        if (!$this->ensureDirectoryExists($this->migrationPath)) {
            return false;
        }

        if (!write_file($filePath, $code)) {
            CLI::error("Error creating migration: {$fileName}");
            return false;
        }

        CLI::write("Migration created: {$fileName}", 'green');
        return true;
    }

    /**
     * Ensures the specified directory exists
     *
     * @param string $path Directory path
     * @return bool True if directory exists or was created successfully
     */
    protected function ensureDirectoryExists(string $path): bool
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                CLI::error("Failed to create directory: {$path}");
                return false;
            }
        }
        return true;
    }

    /**
     * Generates model class code
     *
     * @param string $modelName The model class name
     * @return string The generated model code
     */
    protected function generateModelCode(string $modelName): string
    {
        return <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class {$modelName} extends Model
{
    /**
     * The table associated with the model
     * 
     * @var string
     */
    protected \$table = '{$this->getTableName($modelName)}';
}

EOT;
    }

    /**
     * Generates migration code for creating a table
     *
     * @param string $tableName The table name
     * @return string The generated migration code
     */
    protected function generateMigrationCode(string $tableName): string
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
     *
     * @return void
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
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};

EOT;
    }

    /**
     * Converts a model name to a table name
     * 
     * Converts PascalCase to snake_case and pluralizes
     *
     * @param string $modelName The model class name
     * @return string The table name
     */
    protected function getTableName(string $modelName): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName)) . 's';
    }
}

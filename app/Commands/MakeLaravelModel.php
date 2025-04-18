<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Exception;
use Illuminate\Support\Str; // REQUIRED: For Str::plural() and Str::snake()

class MakeLaravelModel extends BaseCommand
{
    /** @var string */
    protected $group = 'Database'; // Or 'Generators'
    /** @var string */
    protected $name = 'make:laravel-model';
    /** @var string */
    protected $description = 'Create a new Laravel-style Eloquent model with optional migration.';
    /** @var string */
    protected $usage = 'make:laravel-model [<name>] [options]'; // Name is now optional in usage
    /** @var array */
    protected $arguments = [
        'name' => 'The model class name (e.g., User or Common/User).',
    ];
    /** @var array */
    protected $options = [
        '-m' => 'Create a new migration file for the model.',
        '--force' => 'Force overwrite existing model file.',
    ];

    /** Standard exit codes */
    private const EXIT_SUCCESS = 0;
    private const EXIT_ERROR   = 1;

    /**
     * Runs the command.
     *
     * @param array $params Command parameters and options.
     * @return int Exit code.
     */
    public function run(array $params): int
    {
        helper('filesystem'); // Ensure filesystem helper is loaded

        // 1. Get Model Name (Prompt if missing)
        $fullModelName = $params[0] ?? CLI::prompt('Model class name (e.g., User or Common/User)');
        if (empty($fullModelName)) {
            CLI::error('Model name cannot be empty.');
            return self::EXIT_ERROR;
        }

        // 2. Resolve model details
        $details = $this->resolveModelDetails($fullModelName);
        if (!$details) {
            return self::EXIT_ERROR; // Error already shown in resolver
        }

        // 3. Create the Model file
        $modelCreated = $this->createModelFile($details, $params);
        if ($modelCreated !== self::EXIT_SUCCESS) {
            return $modelCreated; // Return the error code from createModelFile
        }

        // 4. Create Migration if requested
        if (CLI::getOption('m')) {
            $migrationCreated = $this->createMigrationFile($details['baseClassName']);
            if ($migrationCreated !== self::EXIT_SUCCESS) {
                CLI::error("Model was created, but migration creation failed.");
                // Logic unchanged: still returns success even if migration fails
            }
        }

        return self::EXIT_SUCCESS;
    }

    /**
     * Resolves class name, namespace, and file paths from the input name.
     *
     * @param string $name Input model name (can include subdirectories).
     * @return array|false Associative array with details or false on error.
     */
    private function resolveModelDetails(string $name): array|false
    {
        // Normalize the input name: replace backslashes with forward slashes, trim slashes and spaces
        $normalizedName = trim(str_replace('\\', '/', $name), '/ ');

        // Validate the name format: should be PascalCase with optional subdirectories
        if (!preg_match('/^([A-Z][a-zA-Z0-9]*\/)*[A-Z][a-zA-Z0-9]*$/', $normalizedName)) {
            CLI::error("Invalid model name format. Use PascalCase names, optionally separated by forward slashes (e.g., User, Common/User, Admin/Auth/Role).");
            return false;
        }

        // Extract the base class name (last part after slashes)
        $baseClassName = basename($normalizedName);

        // Extract the subdirectory part (before the last slash)
        $subNamespacePart = trim(dirname($normalizedName), './ ');

        // Build the full namespace: App\Models + subdirectory namespaces
        $fullNamespace = 'App\\Models';
        if ($subNamespacePart !== '.' && $subNamespacePart !== '') {
            $fullNamespace .= '\\' . str_replace('/', '\\', $subNamespacePart);
        }

        // Build the target directory path
        $baseModelPath = APPPATH . 'Models';
        $targetDir = $baseModelPath;
        if ($subNamespacePart !== '.' && $subNamespacePart !== '') {
            $targetDir .= DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subNamespacePart);
        }

        // Build the target file path
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $baseClassName . '.php';

        // Relative path for display
        $relativeTargetFile = str_replace(APPPATH, 'app/', $targetFile);

        return compact('baseClassName', 'subNamespacePart', 'fullNamespace', 'targetDir', 'targetFile', 'relativeTargetFile');
    }

    /**
     * Creates the model file.
     *
     * @param array $details Resolved model details from resolveModelDetails().
     * @param array $params Command parameters (for --force option).
     * @return int Exit code (EXIT_SUCCESS or EXIT_ERROR).
     */
    private function createModelFile(array $details, array $params): int
    {
        ['targetFile' => $targetFile, 'targetDir' => $targetDir, 'relativeTargetFile' => $relativeTargetFile] = $details;

        // Check if file exists and handle --force option
        $force = $params['force'] ?? CLI::getOption('force') ?? false;
        if (!$force && file_exists($targetFile)) {
            CLI::error("Model already exists: " . CLI::color($relativeTargetFile, 'light_cyan'));
            CLI::write('Use the --force option to overwrite.', 'yellow');
            return self::EXIT_ERROR;
        }

        // Ensure the directory exists
        if (!$this->ensureDirectoryExists($targetDir)) {
            return self::EXIT_ERROR; // Error already shown
        }

        // Generate the model code
        $code = $this->generateModelCode($details);

        // Write the file
        if (!write_file($targetFile, $code)) {
            CLI::error("Error creating model file: {$relativeTargetFile}");
            return self::EXIT_ERROR;
        }

        CLI::write("Model created successfully: " . CLI::color($relativeTargetFile, 'green'));
        return self::EXIT_SUCCESS;
    }

    /**
     * Creates a migration file for the model.
     *
     * @param string $baseModelName The base model class name (without namespace path).
     * @return int Exit code (EXIT_SUCCESS or EXIT_ERROR).
     */
    private function createMigrationFile(string $baseModelName): int
    {
        // Configuration: Define where Laravel-style migrations are stored
        $migrationBasePath = APPPATH . 'Database/Laravel-Migrations';

        // Generate table name from base model name
        $tableName = $this->getTableName($baseModelName);

        // Generate migration class name and file name
        $migrationName = "create_{$tableName}_table";
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$migrationName}.php";
        $targetDir = rtrim($migrationBasePath, DIRECTORY_SEPARATOR);
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        $relativeTargetFile = str_replace(APPPATH, 'app/', $targetFile);

        // Ensure the directory exists
        if (!$this->ensureDirectoryExists($targetDir)) {
            return self::EXIT_ERROR;
        }

        // Generate migration code
        $code = $this->generateMigrationCode($tableName);

        // Write the migration file
        if (!write_file($targetFile, $code)) {
            CLI::error("Error creating migration file: {$relativeTargetFile}");
            return self::EXIT_ERROR;
        }

        CLI::write("Migration created successfully: " . CLI::color($relativeTargetFile, 'green'));
        return self::EXIT_SUCCESS;
    }

    /**
     * Ensures the specified directory exists, creating it if necessary.
     *
     * @param string $directory Absolute path to the directory.
     * @return bool True on success or if directory already exists, false on failure.
     */
    private function ensureDirectoryExists(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }
        try {
            if (!mkdir($directory, 0755, true)) {
                CLI::error("Error: Could not create directory: {$directory}");
                return false;
            }
            CLI::write("Directory created: " . str_replace(APPPATH, 'app/', $directory), 'dark_gray');
            return true;
        } catch (Exception $e) {
            CLI::error("Error creating directory: {$directory}. Reason: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generates model class code using resolved details.
     *
     * @param array $details Resolved model details.
     * @return string The generated model code.
     */
    private function generateModelCode(array $details): string
    {
        ['fullNamespace' => $fullNamespace, 'baseClassName' => $baseClassName] = $details;
        $tableName = $this->getTableName($baseClassName);

        // Define fillable property (initially empty)
        $fillableProperty = 'protected $fillable = [];';

        return <<<PHP
<?php

namespace {$fullNamespace};

use Illuminate\Database\Eloquent\Model;

class {$baseClassName} extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected \$table = '{$tableName}';

    /**
     * The attributes that are mass assignable.
     *
     * Add column names here to allow mass assignment (e.g., using create() or update()).
     *
     * @var array<int, string>
     */
    {$fillableProperty}

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected \$hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected \$casts = [];
}
PHP;
    }

    /**
     * Generates migration code for creating a table.
     *
     * @param string $tableName The table name.
     * @return string The generated migration code.
     */
    private function generateMigrationCode(string $tableName): string
    {
        return <<<PHP
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
PHP;
    }

    /**
     * Converts a base model name (PascalCase) to a table name (snake_case, plural).
     * Requires illuminate/support package.
     *
     * @param string $baseModelName The base model class name (e.g., User, ProductCategory).
     * @return string The table name (e.g., users, product_categories).
     */
    protected function getTableName(string $baseModelName): string
    {
        return Str::plural(Str::snake($baseModelName));
    }
}

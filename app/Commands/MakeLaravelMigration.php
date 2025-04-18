<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;
use Illuminate\Support\Str;
use Exception;

/**
 * Laravel Migration Generator Command
 *
 * Creates Laravel-style migration files within the designated
 * migration directory for CodeIgniter 4 projects using Eloquent/Capsule.
 * Supports generating 'create', 'modify', or generic migration templates.
 */
class MakeLaravelMigration extends BaseCommand
{
    /**
     * The command's group used in spark list.
     *
     * @var string
     */
    protected $group = 'Database'; // Or 'Generators'

    /**
     * The command's name.
     *
     * @var string
     */
    protected $name = 'make:laravel-migration';

    /**
     * The command's short description.
     *
     * @var string
     */
    protected $description = 'Creates a new Laravel-style migration file.';

    /**
     * The command's usage instructions.
     *
     * @var string
     */
    protected $usage = 'make:laravel-migration [<name>] [--table=<table>] [--force]';

    /**
     * The command's defined arguments.
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'name' => 'The migration name (e.g., CreateUsersTable, AddEmailToPostsTable). Optional, will prompt if empty.',
    ];

    /**
     * The command's defined options.
     * Note: --table value is parsed manually via argv due to observed issues with CLI::getOption.
     *
     * @var array<string, string>
     */
    protected $options = [
        '--table' => 'Specify the table name for generating a "modify" migration template.',
        '--force' => 'Force overwrite if a file with the exact same name exists (use with caution).',
    ];

    /**
     * Base path where Laravel-style migration files will be stored.
     *
     * @var string
     */
    protected string $migrationPath = APPPATH . 'Database/Laravel-Migrations/';

    /**
     * Standard Command Exit Codes.
     */
    private const EXIT_SUCCESS = 0;
    private const EXIT_ERROR   = 1;

    //--------------------------------------------------------------------------
    // Command Execution
    //--------------------------------------------------------------------------

    /**
     * Executes the command logic.
     *
     * @param array $params Command parameters passed by Spark.
     * @return int Exit code (EXIT_SUCCESS or EXIT_ERROR).
     */
    public function run(array $params): int
    {
        // Ensure CodeIgniter's filesystem helper is available for write_file()
        helper('filesystem');
        // Ensure Laravel's support package is installed for Str::snake()
        if (!class_exists(Str::class)) {
            CLI::error('Missing dependency: illuminate/support. Please run: composer require illuminate/support');
            return self::EXIT_ERROR;
        }

        // 1. Determine Migration Name
        $migrationName = $this->getMigrationName($params);
        if ($migrationName === null) {
            return self::EXIT_ERROR; // Error message shown in getMigrationName
        }

        // 2. Determine Explicit Table Name (from --table option via argv)
        $explicitTableName = $this->getTableOptionFromArgv();

        // 3. Create the Migration File
        if ($this->createMigrationFile($migrationName, $explicitTableName)) {
            return self::EXIT_SUCCESS;
        } else {
            // Specific error messages should have been shown by createMigrationFile or its helpers
            return self::EXIT_ERROR;
        }
    }

    //--------------------------------------------------------------------------
    // Core Logic
    //--------------------------------------------------------------------------

    /**
     * Orchestrates the creation of the migration file.
     *
     * @param string $migrationName The descriptive name of the migration (e.g., CreateUsersTable).
     * @param string|null $explicitTableName Table name provided via --table option, if any.
     * @return bool True on successful file creation, false otherwise.
     */
    protected function createMigrationFile(string $migrationName, ?string $explicitTableName): bool
    {
        // Use Laravel Str helper for reliable snake_case conversion
        $snakeCaseName = Str::snake($migrationName);
        $fileName = $this->generateFileName($snakeCaseName);
        $targetFile = rtrim($this->migrationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        $relativeTargetFile = str_replace(APPPATH, 'app/', $targetFile); // For user-friendly output

        // Validate file doesn't already exist (unless --force is used)
        if (!$this->validateMigrationDoesNotExist($targetFile, $relativeTargetFile)) {
            $force = CLI::getOption('force') ?? false; // Use CLI::getOption for simple boolean flags
            if (!$force) {
                CLI::write('Use the --force option to overwrite if necessary (use with caution!).', 'yellow');
                return false;
            } else {
                CLI::write("Overwriting existing file due to --force option: {$relativeTargetFile}", 'light_red');
            }
        }

        // Ensure the target directory exists
        if (!$this->ensureMigrationDirectoryExists()) {
            return false;
        }

        // Determine migration type and generate code
        $code = $this->generateMigrationCode($migrationName, $explicitTableName);

        // Write the generated code to the file
        if ($this->writeMigrationFileContent($targetFile, $relativeTargetFile, $code)) {
            CLI::write("Migration created successfully: " . CLI::color($relativeTargetFile, 'green'));
            return true;
        }

        return false; // Error message shown by writeMigrationFileContent
    }

    /**
     * Determines the migration type and generates the corresponding code.
     *
     * @param string $migrationName Original migration name provided by user.
     * @param string|null $explicitTableName Table name from --table option.
     * @return string Generated PHP code for the migration file.
     */
    private function generateMigrationCode(string $migrationName, ?string $explicitTableName): string
    {
        if (!empty($explicitTableName)) {
            // --table option forces a "modify" template
            CLI::write("Generating MODIFY migration for table: " . CLI::color($explicitTableName, 'cyan'));
            return $this->generateModifyMigrationCode($explicitTableName);
        }

        // No --table option, try to infer table name for a "create" migration
        $inferredTableName = $this->inferTableNameForCreate($migrationName);
        if ($inferredTableName) {
            CLI::write("Generating CREATE migration for table: " . CLI::color($inferredTableName, 'cyan'));
            return $this->generateCreateMigrationCode($inferredTableName);
        }

        // Cannot infer, generate a generic template
        CLI::write("Generating GENERIC migration (could not infer table from name, use --table=<table> for specific table modifications)", 'cyan');
        return $this->generateGenericMigrationCode();
    }

    //--------------------------------------------------------------------------
    // Input / Parameter Handling
    //--------------------------------------------------------------------------

    /**
     * Gets the migration name from parameters or prompts the user.
     *
     * @param array $params Command parameters.
     * @return string|null The migration name, or null if empty after prompt.
     */
    private function getMigrationName(array $params): ?string
    {
        $migrationName = $params[0] ?? null;
        if (empty($migrationName)) {
            $migrationName = CLI::prompt('Migration name (e.g., CreateUsersTable)');
        }

        if (empty($migrationName)) {
            CLI::error('Migration name cannot be empty.');
            return null;
        }
        return $migrationName;
    }

    /**
     * Extracts the value of the --table option directly from command line arguments ($argv).
     * Required workaround if CLI::getOption('--table') isn't reliably parsing '--table=value'.
     *
     * @return string|null The table name if found, null otherwise.
     */
    private function getTableOptionFromArgv(): ?string
    {
        global $argv; // Access raw command line arguments
        $tableName = null;

        if (!isset($argv) || !is_array($argv)) {
            CLI::write('Warning: Could not access global $argv.', 'yellow');
            return null; // Safety check
        }

        foreach ($argv as $arg) {
            // Look for '--table=some_value'
            if (str_starts_with($arg, '--table=')) { // PHP 8+ str_starts_with
                // if (strpos($arg, '--table=') === 0) { // PHP < 8 equivalent
                $tableName = substr($arg, 8); // Length of '--table='
                break; // Found it
            }
        }

        // Ensure empty string isn't returned if '--table=' was passed with no value
        return ($tableName === null || $tableName === '') ? null : $tableName;
    }

    //--------------------------------------------------------------------------
    // Code Generation Templates
    //--------------------------------------------------------------------------

    /**
     * Generates migration code for creating a new table.
     *
     * @param string $tableName The table name.
     * @return string The migration code.
     */
    private function generateCreateMigrationCode(string $tableName): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating the '{$tableName}' table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the {$tableName} table.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id(); // Standard auto-incrementing primary key

            // TODO: Add application-specific columns here
            // Examples:
            // \$table->string('name');
            // \$table->text('description')->nullable();

            \$table->timestamps(); // created_at and updated_at columns
            // \$table->softDeletes(); // Optional: deleted_at column
        });

        CLI::write("Schema::create('{$tableName}', ...) executed.", 'dark_gray'); // Example CLI feedback
    }

    /**
     * Reverse the migrations.
     * Drops the {$tableName} table.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
        CLI::write("Schema::dropIfExists('{$tableName}') executed.", 'dark_gray'); // Example CLI feedback
    }
};

PHP;
    }

    /**
     * Generates migration code for modifying an existing table.
     *
     * @param string $tableName The table name.
     * @return string The migration code.
     */
    private function generateModifyMigrationCode(string $tableName): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for modifying the '{$tableName}' table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * Apply changes to the {$tableName} table.
     */
    public function up(): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            //Add or modify columns here;
        });
    }

    /**
     * Reverse the migrations.
     * Revert changes applied to the {$tableName} table in the up() method.
     */
    public function down(): void
    {
        Schema::table('{$tableName}', function (Blueprint \$table) {
            //Revert changes here;
        });
    }
};

PHP;
    }

    /**
     * Generates generic migration code when table cannot be inferred.
     *
     * @return string The migration code.
     */
    private function generateGenericMigrationCode(): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // If using raw DB statements

/**
 * Generic migration for schema or data changes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * Apply necessary changes.
     */
    public function up(): void
    {
        // Implement migration logic here.
        // This could involve Schema::table(), Schema::create(),
    }

    /**
     * Reverse the migrations.
     * Revert the changes made in the up() method.
     */
    public function down(): void
    {
        // TODO: Reverse the logic implemented in the up() method.
    }
};
PHP;
    }

    //--------------------------------------------------------------------------
    // File System and Naming Helpers
    //--------------------------------------------------------------------------

    /**
     * Infers the table name for a "create" migration using naming conventions.
     *
     * @param string $migrationName PascalCase or snake_case migration name.
     * @return string|null The inferred table name (e.g., 'users', 'product_categories') or null.
     */
    protected function inferTableNameForCreate(string $migrationName): ?string
    {
        // Convert to snake_case for consistent parsing
        $snakeCase = Str::snake($migrationName);

        // Match 'create_TABLE_NAME_table' pattern
        if (preg_match('/^create_([a-z0-9_]+?)_table$/', $snakeCase, $matches)) {
            return $matches[1]; // Return the captured table name part
        }

        return null; // Pattern not matched
    }

    /**
     * Generates a timestamped filename for the migration.
     * Example: 2023_10_27_123456_create_users_table.php
     *
     * @param string $snakeCaseName The snake_case name of the migration.
     * @return string The generated filename.
     */
    private function generateFileName(string $snakeCaseName): string
    {
        $timestamp = date('Y_m_d_His');
        return "{$timestamp}_{$snakeCaseName}.php";
    }

    /**
     * Validates that the specific migration *filename* does not exist.
     * Outputs error if file exists.
     *
     * @param string $filePath The full file path to check.
     * @param string $relativeFileName The relative file name for display purposes.
     * @return bool True if the migration file does not exist, false otherwise.
     */
    private function validateMigrationDoesNotExist(string $filePath, string $relativeFileName): bool
    {
        if (file_exists($filePath)) {
            CLI::error("Migration file already exists: " . CLI::color($relativeFileName, 'light_cyan'));
            return false;
        }
        return true;
    }

    /**
     * Ensures the base migration directory exists, creating it if necessary.
     *
     * @return bool True if directory exists or was created successfully.
     */
    private function ensureMigrationDirectoryExists(): bool
    {
        $path = rtrim($this->migrationPath, DIRECTORY_SEPARATOR);
        if (is_dir($path)) {
            return true; // Already exists
        }

        // Directory doesn't exist, attempt to create it
        CLI::write("Migration directory not found, attempting to create: " . str_replace(APPPATH, 'app/', $path), 'dark_gray');
        try {
            if (!mkdir($path, 0755, true)) { // Use standard permissions, recursive
                CLI::error("Error: Could not create migration directory: {$path}. Check permissions.");
                return false;
            }
            CLI::write("Migration directory created successfully.", 'green');
            return true;
        } catch (Exception $e) {
            CLI::error("Exception creating migration directory: {$path}. Reason: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Writes the generated code content to the migration file.
     * Outputs error if writing fails.
     *
     * @param string $filePath The full file path to write to.
     * @param string $relativeFileName The relative file name for display purposes.
     * @param string $code The PHP code content to write.
     * @return bool True on successful write, false otherwise.
     */
    private function writeMigrationFileContent(string $filePath, string $relativeFileName, string $code): bool
    {
        if (!write_file($filePath, $code)) {
            CLI::error("Error writing migration file: {$relativeFileName}. Check permissions.");
            return false;
        }
        return true;
    }
}

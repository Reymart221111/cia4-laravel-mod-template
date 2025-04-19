<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Autoload;
use Exception;

/**
 * Laravel Rule Generator Command
 * 
 * Creates new Laravel-style validation rule classes in the application with
 * support for subdirectories.
 */
class MakeLaravelRule extends BaseCommand
{
    /**
     * The command's group.
     *
     * @var string
     */
    protected $group = 'Generators';

    /**
     * The command's name.
     *
     * @var string
     */
    protected $name = 'make:laravel-rule';

    /**
     * The command's description.
     *
     * @var string
     */
    protected $description = 'Generates a new Laravel-style validation rule class.';

    /**
     * The command's usage.
     *
     * @var string
     */
    protected $usage = 'make:laravel-rule <name> [options]';

    /**
     * The command's arguments.
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'name' => 'The name of the rule class (e.g., NoObsceneWord or Common/NoObsceneWord).',
    ];

    /**
     * The command's options.
     *
     * @var array<string, string>
     */
    protected $options = [
        '--force' => 'Force overwrite existing file.',
    ];

    /**
     * Standard exit codes.
     */
    private const EXIT_SUCCESS = 0;
    private const EXIT_ERROR = 1;

    /**
     * Executes the command.
     *
     * @param array $params Command parameters and options.
     * @return int Exit code.
     */
    public function run(array $params): int
    {
        helper('filesystem'); // Ensure filesystem helper is loaded

        // Get and validate the rule name
        $name = $this->getRuleName($params);
        if ($name === null) {
            return self::EXIT_ERROR; // Error message already shown in getRuleName
        }

        // Determine class details and paths
        $details = $this->resolveTargetDetails($name);
        [
            'className' => $className,
            'fullNamespace' => $fullNamespace,
            'targetDir' => $targetDir,
            'targetFile' => $targetFile
        ] = $details;

        // Ensure the target directory exists
        if (!$this->ensureDirectoryExists($targetDir)) {
            return self::EXIT_ERROR;
        }

        // Check for existing file and --force option
        $force = $params['force'] ?? CLI::getOption('force') ?? false;
        if (!$force && file_exists($targetFile)) {
            $this->showFileExistsError($targetFile);
            return self::EXIT_ERROR;
        }

        // Generate the file content
        $content = $this->generateContent($fullNamespace, $className);

        // Write the file
        if ($this->writeFileContent($targetFile, $content)) {
            CLI::write("Rule created successfully: " . CLI::color(str_replace(APPPATH, 'app/', $targetFile), 'green'));
            return self::EXIT_SUCCESS;
        } else {
            CLI::error("Error writing file: " . str_replace(APPPATH, 'app/', $targetFile));
            return self::EXIT_ERROR;
        }
    }

    /**
     * Prompts for or retrieves the rule name from parameters.
     *
     * @param array $params Command parameters
     * @return string|null Rule name or null on error
     */
    private function getRuleName(array $params): ?string
    {
        $name = $params[0] ?? CLI::prompt('Rule class name');

        if (empty($name)) {
            CLI::error('You must provide a rule class name.');
            return null;
        }
        return $name;
    }

    /**
     * Resolves class name, namespace, and file paths from the input name.
     *
     * @param string $name Input rule name (can include subdirectories)
     * @return array Associative array with className, fullNamespace, targetDir, targetFile
     */
    private function resolveTargetDetails(string $name): array
    {
        // Normalize separators and remove leading/trailing slashes
        $normalizedName = trim(str_replace('\\', '/', $name), '/ ');

        $className = basename($normalizedName);

        // Get the directory part; will be '.' if no directory specified
        $subNamespacePart = trim(dirname($normalizedName), './ ');

        // Build the full namespace
        $fullNamespace = 'App\\Rules';
        if ($subNamespacePart !== '.' && $subNamespacePart !== '') {
            $fullNamespace .= '\\' . str_replace('/', '\\', $subNamespacePart);
        }

        // Build the target directory and file paths
        $basePath = APPPATH . 'Rules';
        $targetDir = $basePath;
        if ($subNamespacePart !== '.' && $subNamespacePart !== '') {
            // Use DIRECTORY_SEPARATOR for cross-platform compatibility
            $targetDir .= DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subNamespacePart);
        }
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $className . '.php';

        return compact('className', 'fullNamespace', 'targetDir', 'targetFile');
    }

    /**
     * Ensures the specified directory exists, creating it if necessary.
     *
     * @param string $directory Absolute path to the directory
     * @return bool True on success or if directory already exists, false on failure
     */
    private function ensureDirectoryExists(string $directory): bool
    {
        if (is_dir($directory)) {
            return true; // Already exists
        }

        try {
            // Create recursively with appropriate permissions
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
     * Displays the error message when a file already exists.
     *
     * @param string $targetFile Absolute path to the file
     * @return void
     */
    private function showFileExistsError(string $targetFile): void
    {
        CLI::error("File already exists: " . CLI::color(str_replace(APPPATH, 'app/', $targetFile), 'light_cyan'));
        CLI::write('Use the --force option to overwrite.', 'yellow');
    }

    /**
     * Generates the PHP content for the rule class file.
     *
     * @param string $namespace The full namespace for the class
     * @param string $className The class name
     * @return string The generated PHP code
     */
    private function generateContent(string $namespace, string $className): string
    {
        $template = $this->getTemplate();
        return str_replace(
            ['{namespace}', '{className}'],
            [$namespace, $className],
            $template
        );
    }

    /**
     * Writes the generated content to the target file.
     *
     * @param string $targetFile Absolute path to the file
     * @param string $content The content to write
     * @return bool True on success, false on failure
     */
    private function writeFileContent(string $targetFile, string $content): bool
    {
        return write_file($targetFile, $content);
    }

    /**
     * Gets the template content for the rule class.
     *
     * @return string The template content
     */
    private function getTemplate(): string
    {
        return <<<PHP
<?php

namespace {namespace};

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class {className} implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  \$attribute The attribute name being validated
     * @param  mixed   \$value     The value of the attribute
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  \$fail The failure callback
     * @return void
     */
    public function validate(string \$attribute, mixed \$value, Closure \$fail): void
    {
        // TODO: Implement validation logic
        // Example:
        // if (!preg_match('/^[a-zA-Z0-9]+$/', \$value)) {
        //     \$fail('The :attribute must be alphanumeric.');
        // }
    }
}

PHP;
    }
}

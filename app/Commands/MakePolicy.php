<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Autoload;

/**
 * Policy Generator Command
 * 
 * Creates new policy class files in the application with optional
 * subdirectory support and model-specific templates.
 */
class MakePolicy extends BaseCommand
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
    protected $name = 'make:policy';

    /**
     * The command's description.
     *
     * @var string
     */
    protected $description = 'Create a new policy class';

    /**
     * The command's usage.
     *
     * @var string
     */
    protected $usage = 'make:policy [PolicyName] [options]';

    /**
     * The command's arguments.
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'PolicyName' => 'The name of the policy class (use slashes for subdirectories)',
    ];

    /**
     * The command's options.
     *
     * @var array<string, string>
     */
    protected $options = [
        '--model' => 'Generate a policy for the specified model',
    ];

    /**
     * Run the policy generation command.
     *
     * @param array $params Command parameters
     * @return void
     */
    public function run(array $params)
    {
        $policyName = array_shift($params);

        if (empty($policyName)) {
            $policyName = CLI::prompt('Policy name');
        }

        $model = $this->extractModelOptionFromArguments();
        $this->createPolicy($policyName, $model);
    }

    /**
     * Extract the model name from command arguments.
     *
     * @return string|null
     */
    protected function extractModelOptionFromArguments(): ?string
    {
        global $argv;
        $modelName = null;

        foreach ($argv as $arg) {
            if (strpos($arg, '--model=') === 0) {
                $modelName = substr($arg, 8);
                break;
            }
        }

        return $modelName;
    }

    /**
     * Create a policy file with optional model-specific template.
     *
     * @param string $policyName The name of the policy (with optional subdirectories)
     * @param string|null $model The associated model name if any
     * @return void
     */
    protected function createPolicy(string $policyName, ?string $model = null)
    {
        helper('filesystem');

        // Process subdirectories in the policy name
        $segments = explode('/', $policyName);
        $className = end($segments);
        $className = $this->sanitizeClassName($className);

        // Ensure class name has proper suffix
        if (!str_ends_with($className, 'Policy')) {
            $className .= 'Policy';
        }

        // Update the path segments with sanitized class name
        $segments[count($segments) - 1] = $className;
        $relativePath = implode('/', $segments);

        // Setup directory structure
        $baseDirectory = APPPATH . 'Policies';
        $subDirectory = dirname($baseDirectory . '/' . $relativePath);

        // Create directories if needed
        if (!is_dir($subDirectory)) {
            mkdir($subDirectory, 0777, true);
        }

        $path = $baseDirectory . '/' . $relativePath . '.php';

        // Prevent overwriting existing files
        if (file_exists($path)) {
            CLI::error($relativePath . ' already exists!');
            return;
        }

        // Build the appropriate namespace with subdirectories
        $namespaceSegments = ['App\\Policies'];
        $subdirs = explode('/', dirname($relativePath));

        if ($subdirs[0] !== '.') {
            foreach ($subdirs as $dir) {
                if (!empty($dir)) {
                    $namespaceSegments[] = $this->sanitizeClassName($dir);
                }
            }
        }

        $namespace = implode('\\', $namespaceSegments);

        // Generate the template based on type
        $template = $model
            ? $this->getModelPolicyTemplate($className, $model, $namespace)
            : $this->getBasicPolicyTemplate($className, $namespace);

        // Write the file
        if (write_file($path, $template)) {
            CLI::write('Policy created: ' . CLI::color($relativePath, 'green'));
        } else {
            CLI::error('Error creating policy file!');
        }
    }

    /**
     * Generate a policy template for model-specific policies.
     *
     * @param string $policyName The name of the policy class
     * @param string $model The associated model name
     * @param string $namespace The namespace to use for the policy
     * @return string
     */
    protected function getModelPolicyTemplate(string $policyName, string $model, string $namespace)
    {
        // Normalize model name
        $modelName = str_replace('Model', '', $model);

        if (!str_contains($modelName, '\\')) {
            $modelName = 'App\\Models\\' . $modelName;
        }

        $modelClass = $this->getModelClass($modelName);
        $isUserModel = ($modelClass === 'User');

        $imports = $isUserModel
            ? "use {$modelName};"
            : "use App\\Models\\User;\nuse {$modelName};";

        return <<<EOD
<?php

namespace {$namespace};

{$imports}

class {$policyName}
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User \$user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User \$user, {$modelClass} \$model): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User \$user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User \$user, {$modelClass} \$model): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User \$user, {$modelClass} \$model): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User \$user, {$modelClass} \$model): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User \$user, {$modelClass} \$model): bool
    {
        //
    }
}
EOD;
    }

    /**
     * Generate a basic policy template.
     *
     * @param string $policyName The name of the policy class
     * @param string $namespace The namespace to use for the policy
     * @return string
     */
    protected function getBasicPolicyTemplate(string $policyName, string $namespace)
    {
        return <<<EOD
<?php

namespace {$namespace};

use App\\Models\\User;

class {$policyName}
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
}
EOD;
    }

    /**
     * Extract the class name from a fully qualified model name.
     *
     * @param string $modelName The fully qualified model name
     * @return string
     */
    protected function getModelClass(string $modelName)
    {
        $parts = explode('\\', $modelName);
        return end($parts);
    }

    /**
     * Sanitize and normalize a class name.
     *
     * @param string $name The raw class name
     * @return string
     */
    protected function sanitizeClassName(string $name): string
    {
        $name = str_replace('.php', '', $name);
        $name = str_replace(['-', '_'], ' ', $name);
        $name = str_replace(' ', '', ucwords($name));

        return $name;
    }

    /**
     * Get an option value from CLI input.
     *
     * @param string $option The option name
     * @return string|null
     */
    protected function getOption(string $option): ?string
    {
        $options = CLI::getOptions();
        return $options[$option] ?? null;
    }
}

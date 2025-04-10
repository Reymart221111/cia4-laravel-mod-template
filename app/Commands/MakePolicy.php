<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Autoload;

class MakePolicy extends BaseCommand
{
    protected $group = 'Generators';
    protected $name = 'make:policy';
    protected $description = 'Create a new policy class';
    protected $usage = 'make:policy [PolicyName] [options]';
    protected $arguments = [
        'PolicyName' => 'The name of the policy class',
    ];
    protected $options = [
        '--model' => 'Generate a policy for the specified model',
    ];

    public function run(array $params)
    {
        $policyName = array_shift($params);

        if (empty($policyName)) {
            $policyName = CLI::prompt('Policy name');
        }

        $policyName = $this->sanitizeClassName($policyName);

        // Extract model name from arguments
        $model = $this->extractModelOptionFromArguments();

        // Create the policy
        $this->createPolicy($policyName, $model);
    }

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

    protected function createPolicy(string $policyName, ?string $model = null)
    {
        helper('filesystem');

        // Make sure we have the correct suffix
        if (!str_ends_with($policyName, 'Policy')) {
            $policyName .= 'Policy';
        }

        // Create the directory if it doesn't exist
        $directory = APPPATH . 'Policies';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        // Check if policy file already exists
        $path = $directory . '/' . $policyName . '.php';
        if (file_exists($path)) {
            CLI::error($policyName . ' already exists!');
            return;
        }

        // Get the policy template
        if ($model) {
            $template = $this->getModelPolicyTemplate($policyName, $model);
        } else {
            $template = $this->getBasicPolicyTemplate($policyName);
        }

        // Write the policy file
        if (write_file($path, $template)) {
            CLI::write('Policy created: ' . CLI::color($policyName, 'green'));
        } else {
            CLI::error('Error creating policy file!');
        }
    }

    protected function getModelPolicyTemplate(string $policyName, string $model)
    {
        // Strip "Model" suffix if present
        $modelName = str_replace('Model', '', $model);

        // Make sure we have correct model name with namespace
        if (!str_contains($modelName, '\\')) {
            $modelName = 'App\\Models\\' . $modelName;
        }
        
        // Get the short class name for use in method parameters
        $modelClass = $this->getModelClass($modelName);
        
        // Check if this is the User model to avoid duplicate imports
        $isUserModel = ($modelClass === 'User');
        
        // Only include the User model import if it's not the same as our target model
        $imports = $isUserModel 
            ? "use {$modelName};" 
            : "use App\\Models\\User;\nuse {$modelName};";

        return <<<EOD
<?php

namespace App\Policies;

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

    protected function getBasicPolicyTemplate(string $policyName)
    {
        return <<<EOD
<?php

namespace App\Policies;

use App\Models\User;

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

    protected function getModelClass(string $modelName)
    {
        $parts = explode('\\', $modelName);
        return end($parts);
    }

    protected function sanitizeClassName(string $name): string
    {
        // Remove file extension if present
        $name = str_replace('.php', '', $name);

        // Convert dashes and underscores to spaces
        $name = str_replace(['-', '_'], ' ', $name);

        // Title case and remove spaces
        $name = str_replace(' ', '', ucwords($name));

        return $name;
    }

    protected function getOption(string $option): ?string
    {
        $options = CLI::getOptions();
        return $options[$option] ?? null;
    }
}
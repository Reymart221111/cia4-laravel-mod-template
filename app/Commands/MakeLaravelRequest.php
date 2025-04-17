<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MakeLaravelRequest extends BaseCommand
{
    protected $group = 'Generators';
    protected $name = 'make:laravel-request';
    protected $description = 'Creates a new Laravel-style Form Request class file.';
    protected $usage = 'make:laravel-request [class_name]';
    protected $arguments = [
        'class_name' => 'The name of the class file to create, including any namespaces.',
    ];

    public function run(array $params)
    {
        $className = $this->getClassName($params);

        if (empty($className)) {
            return;
        }

        [$namespace, $className, $path] = $this->parseClassDetails($className);

        if ($this->fileExists($path)) {
            return;
        }

        $template = $this->buildTemplate($namespace, $className);
        $this->createFile($path, $template);
    }

    protected function getClassName(array $params): string
    {
        $className = array_shift($params);

        if (empty($className)) {
            CLI::error('You must provide a class name.');
            return '';
        }

        return $className;
    }

    protected function parseClassDetails(string $input): array
    {
        // Parse the class name
        $segments = explode('/', $input);
        $className = array_pop($segments);

        // Ensure the class name ends with "Request" if not already
        if (!str_ends_with($className, 'Request')) {
            $className .= 'Request';
        }

        // Set the namespace
        $namespace = 'App\Requests';
        if (!empty($segments)) {
            $namespace .= '\\' . implode('\\', $segments);
        }

        // Create directory structure and get file path
        $directory = $this->createDirectoryStructure($segments);
        $path = $directory . '/' . $className . '.php';

        return [$namespace, $className, $path];
    }

    protected function createDirectoryStructure(array $segments): string
    {
        $directory = APPPATH . 'Requests';

        foreach ($segments as $segment) {
            $directory .= '/' . $segment;
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }

        return $directory;
    }

    protected function fileExists(string $path): bool
    {
        if (file_exists($path)) {
            CLI::error(basename($path, '.php') . ' already exists!');
            return true;
        }

        return false;
    }

    protected function buildTemplate(string $namespace, string $className): string
    {
        return <<<EOD
<?php

namespace {$namespace};

use App\Libraries\Validation\FormRequest;

class {$className} extends FormRequest
{
    public function rules()
    {
        return [
            // Define your validation rules here
        ];
    }
    
    public function messages()
    {
        return [
            // Define your custom messages here (optional)
        ];
    }
    
    public function attributes()
    {
        return [
            // Define your custom attribute names here (optional)
        ];
    }
}
EOD;
    }

    protected function createFile(string $path, string $content): void
    {
        if (write_file($path, $content)) {
            CLI::write('Created: ' . CLI::color($path, 'green'));
        } else {
            CLI::error('Error creating file: ' . $path);
        }
    }
}

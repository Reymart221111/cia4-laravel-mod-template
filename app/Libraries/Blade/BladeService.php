<?php

namespace App\Libraries\Blade;

use Jenssegers\Blade\Blade;
use App\Libraries\Blade\BladeExtension;
use Illuminate\Pagination\Paginator;
use Jenssegers\Blade\Container as BladeContainer;

class BladeService
{
    /**
     * @var Blade Instance of the Blade engine
     */
    protected Blade $blade;

    /**
     * @var array Configuration for Blade
     */
    protected array $config;

    /**
     * Initialize the BladeService with configuration
     */
    public function __construct()
    {
        $this->config = [
            'viewsPath' => APPPATH . 'Views',
            'cachePath' => WRITEPATH . 'cache/blade',
            'componentNamespace' => 'components',
            'componentPath' => APPPATH . 'Views/components',
        ];

        $this->initialize();
    }

    /**
     * Initialize the Blade engine
     */
    protected function initialize(): void
    {
        $this->ensureCacheDirectory();

        $container = new BladeContainer();
        
        $this->blade = new Blade(
            $this->config['viewsPath'],
            $this->config['cachePath'],
            $container
        );

        if (ENVIRONMENT === 'production') {
            $this->blade->getCompiler()->setIsExpired(function() {
                return false; 
            });
            
            $this->blade->getCompiler()->setContentTags('{{', '}}');
            $this->blade->getCompiler()->setEscapedContentTags('{{{', '}}}');
        }

        $this->blade->addNamespace(
            $this->config['componentNamespace'],
            $this->config['componentPath']
        );

        Paginator::useBootstrap();
        $this->applyExtensions();
    }

    /**
     * Ensure the cache directory exists and is writable
     */
    protected function ensureCacheDirectory(): void
    {
        $cachePath = $this->config['cachePath'];

        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }

        if (!is_writable($cachePath)) {
            log_message('error', "Blade cache path is not writable: {$cachePath}");
        }
    }

    /**
     * Apply Blade extensions and customizations
     */
    protected function applyExtensions(): void
    {
        if (!class_exists(BladeExtension::class)) {
            log_message('warning', 'BladeExtension class not found. Custom directives are disabled.');
            return;
        }

        $bladeExtension = new BladeExtension();

        if (method_exists($bladeExtension, 'registerDirectives')) {
            $bladeExtension->registerDirectives($this->blade);
        }
    }

    /**
     * Process view data with extensions
     * 
     * @param array $data The view data to process
     * @return array Processed view data
     */
    public function processData(array $data): array
    {
        if (!class_exists(BladeExtension::class)) {
            return $data;
        }

        $bladeExtension = new BladeExtension();

        if (method_exists($bladeExtension, 'processData')) {
            return $bladeExtension->processData($data);
        }

        return $data;
    }

    /**
     * Filter internal keys from view data
     * 
     * @param array $data The view data to filter
     * @return array Filtered view data
     */
    public function filterInternalKeys(array $data): array
    {
        $internalKeys = [
            '__componentPath',
            '__componentAttributes',
            '__componentData',
            '__componentSlot',
            '__currentSlot',
            'blade',
            'bladeExtension',
            'viewsPath',
            'cachePath',
            'componentNamespace',
            'componentPath',
            'internalKeys',
            'filteredData',
            'render',
            'view',
            'data',
        ];

        return array_filter($data, fn($key) => !in_array($key, $internalKeys), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Render a view with Blade
     * 
     * @param string $view The view identifier in dot notation
     * @param array $data Data to be passed to the view
     * @return string Rendered HTML string
     * @throws \Throwable Rendering exceptions in non-production environments
     */
    public function render(string $view, array $data = []): string
    {
        try {
            $data = $this->processData($data);
            $filteredData = $this->filterInternalKeys($data);

            return $this->blade->make($view, $filteredData)->render();
        } catch (\Throwable $e) {
            log_message('error', "Blade rendering error in view [{$view}]: {$e->getMessage()}\n{$e->getTraceAsString()}");

            if (ENVIRONMENT !== 'production') {
                throw $e;
            }

            return "<!-- View Rendering Error -->";
        }
    }

    /**
     * Get the Blade instance
     * 
     * @return Blade The Blade engine instance
     */
    public function getBlade(): Blade
    {
        return $this->blade;
    }

    /**
     * Compiles all blade views
     * 
     * @param bool $force Force recompilation
     * @return array Compilation results
     */
    public function compileViews(bool $force = false): array
    {
        $filesystem = new \Illuminate\Filesystem\Filesystem();
        $compiler = $this->blade->getCompiler();

        // Get all .blade.php files
        $viewsPath = $this->config['viewsPath'];
        $files = $this->getBladeFiles($viewsPath);

        $results = [];
        foreach ($files as $file) {
            $relativePath = str_replace($viewsPath . '/', '', $file);
            $viewName = str_replace('.blade.php', '', $relativePath);
            $viewName = str_replace('/', '.', $viewName);

            try {
                if ($force || !$compiler->isExpired($viewsPath . '/' . $relativePath)) {
                    $compiler->compile($viewsPath . '/' . $relativePath);
                }
                $results[$viewName] = true;
            } catch (\Exception $e) {
                $results[$viewName] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get all Blade template files recursively
     * 
     * @param string $directory
     * @return array
     */
    protected function getBladeFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if (
                $file->isFile() &&
                (str_ends_with($file->getPathname(), '.blade.php'))
            ) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}

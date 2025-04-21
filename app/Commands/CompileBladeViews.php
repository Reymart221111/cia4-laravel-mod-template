<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Illuminate\Filesystem\Filesystem;

class CompileBladeViews extends BaseCommand
{
    protected $group = 'Blade';
    protected $name = 'blade:compile';
    protected $description = 'Pre-compiles all Blade templates';

    public function run(array $params)
    {
        CLI::write('Starting Blade view compilation...', 'yellow');
        
        // Get blade service but we'll access the compiler directly
        $bladeService = service('blade');
        $blade = $bladeService->getBlade();
        
        // For Jenssegers/Blade, we can access the compiler like this
        $compiler = $blade->compiler();
        $viewsPath = APPPATH . 'Views';
        $filesystem = new Filesystem();
        
        // Get all blade templates
        $files = $this->findBladeTemplates($viewsPath);
        
        $successful = 0;
        $failed = 0;
        
        foreach ($files as $file) {
            $relativePath = str_replace($viewsPath . '/', '', $file);
            $viewName = str_replace('.blade.php', '', $relativePath);
            $viewName = str_replace('/', '.', $viewName);
            
            try {
                // Force compile the view
                $compiler->compile($file);
                CLI::write("✓ {$viewName}", 'green');
                $successful++;
            } catch (\Exception $e) {
                CLI::error("✗ {$viewName}: {$e->getMessage()}");
                $failed++;
            }
        }
        
        CLI::write("Compilation complete: {$successful} succeeded, {$failed} failed", 'yellow');
    }
    
    protected function findBladeTemplates($directory)
    {
        $files = [];
        $filesystem = new Filesystem();
        
        foreach ($filesystem->allFiles($directory) as $file) {
            if (str_ends_with($file->getPathname(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
}
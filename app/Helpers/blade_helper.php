<?php

use Jenssegers\Blade\Blade;
use App\Libraries\BladeExtension;
use Illuminate\Pagination\Paginator;

/**
 * Load a view file using Blade templating
 *
 * @param string $view
 * @param array $data
 * @return void
 */
function blade_view($view, $data = [])
{
    $viewsPath = APPPATH . '/Views';
    $cachePath = FCPATH . 'writable/cache';
    $blade = new Blade($viewsPath, $cachePath);

    // Initialize basic Paginator configuration
    Paginator::useBootstrap();
    
    // Process paginator objects and add Laravel-style errors handling
    $bladeExtension = new BladeExtension();
    $data = $bladeExtension->processData($data);
    $bladeExtension->registerDirectives($blade);

    echo $blade->make($view, $data)->render();
}
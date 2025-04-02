<?php

use Jenssegers\Blade\Blade;

/**
 * Load a view file
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
    echo $blade->make($view, $data)->render();
}
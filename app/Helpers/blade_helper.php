<?php

use App\Libraries\Blade\BladeService;
use Config\Services;

/**
 * Renders a view file using the Blade templating engine
 *
 * This function serves as the primary interface to the Blade templating system.
 * It handles view rendering through the BladeService, which manages all
 * template processing, extension loading, and error handling.
 *
 * @param string $view   The view identifier in dot notation (e.g. 'pages.users.index')
 * @param array  $data   Data to be passed to the view
 * @param bool   $render If true, returns the output instead of echoing
 * @return mixed         Rendered HTML string or void
 * @throws \Throwable    Re-throws rendering exceptions in non-production environments
 */
function blade_view(string $view, array $data = [], bool $render = false)
{
    $output = service('blade')->render($view, $data);
    
    if ($render) {
        return $output;
    }
    
    echo $output;
}
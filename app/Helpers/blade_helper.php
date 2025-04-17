<?php

use Jenssegers\Blade\Blade;
use App\Libraries\BladeExtension;
use Illuminate\Pagination\Paginator;

/**
 * Renders a view file using the Blade templating engine with custom extensions.
 *
 * This function acts as the primary interface for using Blade within the application.
 * It initializes the Blade engine with appropriate view and cache paths, registers
 * custom namespaces (especially for components), applies application-specific
 * data processing and directive registrations via `BladeExtension`, filters out
 * internal variables to maintain a clean view scope, and handles view rendering
 * with basic error logging.
 *
 * @param string $view   The view identifier. This can be a dot-notation path
 *                       relative to the base views path (e.g., 'pages.users.index')
 *                       or use a registered namespace (e.g., 'components::button').
 * @param array  $data   An associative array of data to be extracted into variables
 *                       available within the Blade view. Defaults to an empty array.
 * @param bool   $render If set to true, the function returns the rendered HTML output
 *                       as a string. If false (default), it echoes the output directly.
 * @return mixed         Returns the rendered HTML string if `$render` is true, otherwise returns void.
 * @throws \Throwable    Re-throws rendering exceptions in non-production environments.
 */
function blade_view(string $view, array $data = [], bool $render = false)
{
    //======================================================================
    // Configuration & Path Setup
    //======================================================================

    $viewsPath = APPPATH . 'Views';         // Root directory containing Blade view files.
    $cachePath = WRITEPATH . 'cache/blade'; // Writable directory for storing compiled Blade templates.
    $componentNamespace = 'components';     // The namespace alias used for Blade components (e.g., 'components::' or <x-...>).

    // *** CRITICAL: Define the actual path to your component view files ***
    // This path is linked to the '$componentNamespace' above. Adjust as needed.
    // Example 1: If components are in `app/Views/components/`
    $componentPath = APPPATH . 'Views/components';
    // Example 2: If components are in `app/Views/contents/components/`
    // $componentPath = APPPATH . 'Views/contents/components';

    //======================================================================
    // Blade Engine Initialization
    //======================================================================

    // Ensure the cache directory exists and is writable.
    if (!is_dir($cachePath)) {
        // Recursively create the directory with permissive rights. Adjust if needed.
        mkdir($cachePath, 0777, true);
    }
    if (!is_writable($cachePath)) {
        log_message('error', "Blade cache path is not writable: {$cachePath}");
        // Consider throwing an exception or returning an error view here,
        // as Blade compilation will likely fail.
    }

    // Instantiate the Blade engine.
    $blade = new Blade($viewsPath, $cachePath);

    // Register the namespace for accessing component views easily.
    $blade->addNamespace($componentNamespace, $componentPath);

    // Initialize default pagination theme (if using Laravel's Paginator features).
    // This can be overridden by data processing in BladeExtension if needed.
    Paginator::useBootstrap(); // Common options: 'bootstrap', 'tailwind', 'simple-bootstrap'

    //======================================================================
    // Custom Extensions & Data Processing via BladeExtension
    //======================================================================

    // Apply application-specific Blade customizations if the extension class exists.
    if (class_exists(BladeExtension::class)) {
        $bladeExtension = new BladeExtension();

        // Allow the extension to preprocess data (add pagination links, error handlers, etc.).
        if (method_exists($bladeExtension, 'processData')) {
            $data = $bladeExtension->processData($data);
        }

        // Allow the extension to register custom directives (@can, @error, @component, etc.).
        if (method_exists($bladeExtension, 'registerDirectives')) {
            $bladeExtension->registerDirectives($blade);
        }
    } else {
        log_message('warning', 'BladeExtension class not found. Custom directives and data processing are disabled.');
    }

    //======================================================================
    // View Data Filtering
    //======================================================================

    // Define keys for internal variables used by this helper or Blade components/directives.
    // These will be filtered out to prevent them from polluting the view's variable scope.
    $internalKeys = [
        // Component/Directive Internals (prefixed with __ for convention)
        '__componentPath',
        '__componentAttributes',
        '__componentData',
        '__componentSlot',
        '__currentSlot',
        // Helper Function Internals
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
        // Common Blade Internals (usually handled, but filter defensively)
        // '__env', '__data',
    ];
    // Create the final data array for the view, excluding internal keys.
    $filteredData = array_filter($data, fn($key) => !in_array($key, $internalKeys), ARRAY_FILTER_USE_KEY);

    //======================================================================
    // View Rendering & Output
    //======================================================================

    $output = ''; // Initialize output buffer.
    try {
        // Render the specified Blade view with the filtered data.
        $output = $blade->make($view, $filteredData)->render();
    } catch (\Throwable $e) {
        // Log rendering errors for debugging purposes.
        log_message('error', "Blade rendering error in view [{$view}]: {$e->getMessage()}\n{$e->getTraceAsString()}");

        // In non-production environments, re-throw the exception for detailed debugging.
        if (ENVIRONMENT !== 'production') {
            throw $e;
        }

        // In production, display a generic error or empty string to avoid exposing details.
        $output = "<!-- View Rendering Error -->"; // Or return an error view component
    }

    // Handle the final output based on the $render flag.
    if ($render) {
        return $output; // Return the rendered HTML as a string.
    }

    echo $output; // Echo the rendered HTML directly to the browser.
    // No explicit return value needed when echoing.
}

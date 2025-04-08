<?php

use Illuminate\Pagination\LengthAwarePaginator;
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

    // Initialize basic Paginator configuration
    \Illuminate\Pagination\Paginator::useBootstrap();

    // Process any paginator objects to add custom rendering
    foreach ($data as $key => $value) {
        if ($value instanceof LengthAwarePaginator) {
            // Add the pagination renderer to the data
            $data[$key]->linksHtml = render_pagination($value);
        }
    }

    // Add custom @error directive 
    $blade->directive('error', function ($expression) {
        return "<?php 
            \$__fieldName = $expression;
            \$__sessionErrors = session('errors') ?? [];
            if (isset(\$__sessionErrors[\$__fieldName])) : 
            \$message = \$__sessionErrors[\$__fieldName]; ?>";
    });

    $blade->directive('enderror', function () {
        return "<?php endif; ?>";
    });

    // Create an errors object that mimics Laravel's $errors variable
    if (!isset($data['errors']) && session('errors')) {
        $data['errors'] = new class(session('errors')) {
            protected $errors;

            public function __construct($errors)
            {
                $this->errors = $errors;
            }

            public function getBag()
            {
                return $this;
            }

            public function has($key)
            {
                return isset($this->errors[$key]);
            }

            public function first($key)
            {
                return $this->has($key) ? $this->errors[$key] : null;
            }
        };
    }

    echo $blade->make($view, $data)->render();
}
<?php
// app/Helpers/BladeHelper.php

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
            // Add the pagination renderer to the data using the configured theme
            $theme = config('Pagination')->theme ?? 'bootstrap';
            // If a specific theme is set for this request, use it
            if (isset($data['paginationTheme'])) {
                $theme = $data['paginationTheme'];
            }
            $data[$key]->linksHtml = render_pagination($value, $theme);
        }
    }

    // Can directive
    $blade->directive('can', function ($expression) {
        return "<?php if(can($expression)): ?>";
    });

    $blade->directive('endcan', function () {
        return "<?php endif; ?>";
    });

    // Cannot directive
    $blade->directive('cannot', function ($expression) {
        return "<?php if(cannot($expression)): ?>";
    });

    $blade->directive('endcannot', function () {
        return "<?php endif; ?>";
    });

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

/**
 * Render pagination links based on the theme
 * 
 * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
 * @param string $theme
 * @return string
 */
function render_pagination($paginator, $theme = 'bootstrap')
{
    // Load the pagination config
    $paginationConfig = config('Pagination');

    // Get the theme renderers
    $renderers = $paginationConfig->renderers ?? [];

    // If the theme exists in renderers, use it
    if (isset($renderers[$theme]) && function_exists($renderers[$theme])) {
        return call_user_func($renderers[$theme], $paginator, $paginationConfig);
    }

    // Fallback to default bootstrap theme
    return render_pagination_bootstrap($paginator, $paginationConfig);
}

/**
 * Bootstrap pagination renderer
 * 
 * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
 * @param object $config
 * @return string
 */
function render_pagination_bootstrap($paginator, $config)
{
    $window = $config->window ?? 3;
    $output = '<ul class="pagination">';

    // Previous Page Link
    if ($paginator->onFirstPage()) {
        $output .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    } else {
        $output .= '<li class="page-item"><a class="page-link" href="' . $paginator->previousPageUrl() . '">&laquo;</a></li>';
    }

    // Page numbers
    $lastPage = $paginator->lastPage();
    $currentPage = $paginator->currentPage();

    // Beginning page numbers
    if ($currentPage <= $window + 2) {
        for ($i = 1; $i <= min($window * 2 + 1, $lastPage); $i++) {
            if ($i == $currentPage) {
                $output .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $output .= '<li class="page-item"><a class="page-link" href="' . $paginator->url($i) . '">' . $i . '</a></li>';
            }
        }

        if ($lastPage > $window * 2 + 1) {
            if ($lastPage > $window * 2 + 2) {
                $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $output .= '<li class="page-item"><a class="page-link" href="' . $paginator->url($lastPage) . '">' . $lastPage . '</a></li>';
        }
    }
    // Ending page numbers
    else if ($currentPage > $lastPage - ($window + 2)) {
        $output .= '<li class="page-item"><a class="page-link" href="' . $paginator->url(1) . '">1</a></li>';

        if ($lastPage - ($window * 2) > 2) {
            $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        for ($i = max(1, $lastPage - ($window * 2)); $i <= $lastPage; $i++) {
            if ($i == $currentPage) {
                $output .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $output .= '<li class="page-item"><a class="page-link" href="' . $paginator->url($i) . '">' . $i . '</a></li>';
            }
        }
    }
    // Middle page numbers
    else {
        $output .= '<li class="page-item"><a class="page-link" href="' . $paginator->url(1) . '">1</a></li>';

        if ($currentPage - $window > 2) {
            $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        for ($i = max(2, $currentPage - $window); $i <= min($lastPage - 1, $currentPage + $window); $i++) {
            if ($i == $currentPage) {
                $output .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $output .= '<li class="page-item"><a class="page-link" href="' . $paginator->url($i) . '">' . $i . '</a></li>';
            }
        }

        if ($currentPage + $window < $lastPage - 1) {
            $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        $output .= '<li class="page-item"><a class="page-link" href="' . $paginator->url($lastPage) . '">' . $lastPage . '</a></li>';
    }

    // Next Page Link
    if ($paginator->hasMorePages()) {
        $output .= '<li class="page-item"><a class="page-link" href="' . $paginator->nextPageUrl() . '">&raquo;</a></li>';
    } else {
        $output .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    }

    $output .= '</ul>';

    return $output;
}

/**
 * Tailwind CSS pagination renderer
 * 
 * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
 * @param object $config
 * @return string
 */
function render_pagination_tailwind($paginator, $config)
{
    $window = $config->window ?? 3;
    $output = '<nav><ul class="flex items-center -space-x-px h-10 text-base">';

    // Previous Page Link
    if ($paginator->onFirstPage()) {
        $output .= '<li><span class="flex items-center justify-center px-4 h-10 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg cursor-not-allowed">&laquo;</span></li>';
    } else {
        $output .= '<li><a href="' . $paginator->previousPageUrl() . '" class="flex items-center justify-center px-4 h-10 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700">&laquo;</a></li>';
    }

    // Page numbers with Tailwind classes
    $lastPage = $paginator->lastPage();
    $currentPage = $paginator->currentPage();

    // Beginning page numbers
    if ($currentPage <= $window + 2) {
        for ($i = 1; $i <= min($window * 2 + 1, $lastPage); $i++) {
            if ($i == $currentPage) {
                $output .= '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-blue-600 border border-gray-300 bg-blue-50">' . $i . '</span></li>';
            } else {
                $output .= '<li><a href="' . $paginator->url($i) . '" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">' . $i . '</a></li>';
            }
        }

        if ($lastPage > $window * 2 + 1) {
            if ($lastPage > $window * 2 + 2) {
                $output .= '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
            }
            $output .= '<li><a href="' . $paginator->url($lastPage) . '" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">' . $lastPage . '</a></li>';
        }
    }
    // Ending page numbers
    else if ($currentPage > $lastPage - ($window + 2)) {
        $output .= '<li><a href="' . $paginator->url(1) . '" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">1</a></li>';

        if ($lastPage - ($window * 2) > 2) {
            $output .= '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
        }

        for ($i = max(1, $lastPage - ($window * 2)); $i <= $lastPage; $i++) {
            if ($i == $currentPage) {
                $output .= '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-blue-600 border border-gray-300 bg-blue-50">' . $i . '</span></li>';
            } else {
                $output .= '<li><a href="' . $paginator->url($i) . '" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">' . $i . '</a></li>';
            }
        }
    }
    // Middle page numbers
    else {
        $output .= '<li><a href="' . $paginator->url(1) . '" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">1</a></li>';

        if ($currentPage - $window > 2) {
            $output .= '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
        }

        for ($i = max(2, $currentPage - $window); $i <= min($lastPage - 1, $currentPage + $window); $i++) {
            if ($i == $currentPage) {
                $output .= '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-blue-600 border border-gray-300 bg-blue-50">' . $i . '</span></li>';
            } else {
                $output .= '<li><a href="' . $paginator->url($i) . '" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">' . $i . '</a></li>';
            }
        }

        if ($currentPage + $window < $lastPage - 1) {
            $output .= '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
        }

        $output .= '<li><a href="' . $paginator->url($lastPage) . '" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">' . $lastPage . '</a></li>';
    }

    // Next Page Link
    if ($paginator->hasMorePages()) {
        $output .= '<li><a href="' . $paginator->nextPageUrl() . '" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700">&raquo;</a></li>';
    } else {
        $output .= '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg cursor-not-allowed">&raquo;</span></li>';
    }

    $output .= '</ul></nav>';

    return $output;
}

/**
 * Bulma CSS pagination renderer
 * 
 * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
 * @param object $config
 * @return string
 */
function render_pagination_bulma($paginator, $config)
{
    $window = $config->window ?? 3;
    $output = '<nav class="pagination is-centered" role="navigation" aria-label="pagination">';

    // Previous and Next buttons
    if ($paginator->onFirstPage()) {
        $output .= '<a class="pagination-previous" disabled>&laquo;</a>';
    } else {
        $output .= '<a class="pagination-previous" href="' . $paginator->previousPageUrl() . '">&laquo;</a>';
    }

    if ($paginator->hasMorePages()) {
        $output .= '<a class="pagination-next" href="' . $paginator->nextPageUrl() . '">&raquo;</a>';
    } else {
        $output .= '<a class="pagination-next" disabled>&raquo;</a>';
    }

    // Page list
    $output .= '<ul class="pagination-list">';

    $lastPage = $paginator->lastPage();
    $currentPage = $paginator->currentPage();

    // Beginning page numbers
    if ($currentPage <= $window + 2) {
        for ($i = 1; $i <= min($window * 2 + 1, $lastPage); $i++) {
            if ($i == $currentPage) {
                $output .= '<li><a class="pagination-link is-current" aria-label="Page ' . $i . '" aria-current="page">' . $i . '</a></li>';
            } else {
                $output .= '<li><a class="pagination-link" href="' . $paginator->url($i) . '" aria-label="Go to page ' . $i . '">' . $i . '</a></li>';
            }
        }

        if ($lastPage > $window * 2 + 1) {
            if ($lastPage > $window * 2 + 2) {
                $output .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';
            }
            $output .= '<li><a class="pagination-link" href="' . $paginator->url($lastPage) . '" aria-label="Go to page ' . $lastPage . '">' . $lastPage . '</a></li>';
        }
    }
    // Ending page numbers
    else if ($currentPage > $lastPage - ($window + 2)) {
        $output .= '<li><a class="pagination-link" href="' . $paginator->url(1) . '" aria-label="Go to page 1">1</a></li>';

        if ($lastPage - ($window * 2) > 2) {
            $output .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';
        }

        for ($i = max(1, $lastPage - ($window * 2)); $i <= $lastPage; $i++) {
            if ($i == $currentPage) {
                $output .= '<li><a class="pagination-link is-current" aria-label="Page ' . $i . '" aria-current="page">' . $i . '</a></li>';
            } else {
                $output .= '<li><a class="pagination-link" href="' . $paginator->url($i) . '" aria-label="Go to page ' . $i . '">' . $i . '</a></li>';
            }
        }
    }
    // Middle page numbers
    else {
        $output .= '<li><a class="pagination-link" href="' . $paginator->url(1) . '" aria-label="Go to page 1">1</a></li>';

        if ($currentPage - $window > 2) {
            $output .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';
        }

        for ($i = max(2, $currentPage - $window); $i <= min($lastPage - 1, $currentPage + $window); $i++) {
            if ($i == $currentPage) {
                $output .= '<li><a class="pagination-link is-current" aria-label="Page ' . $i . '" aria-current="page">' . $i . '</a></li>';
            } else {
                $output .= '<li><a class="pagination-link" href="' . $paginator->url($i) . '" aria-label="Go to page ' . $i . '">' . $i . '</a></li>';
            }
        }

        if ($currentPage + $window < $lastPage - 1) {
            $output .= '<li><span class="pagination-ellipsis">&hellip;</span></li>';
        }

        $output .= '<li><a class="pagination-link" href="' . $paginator->url($lastPage) . '" aria-label="Go to page ' . $lastPage . '">' . $lastPage . '</a></li>';
    }

    $output .= '</ul></nav>';

    return $output;
}

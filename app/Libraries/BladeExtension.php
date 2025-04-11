<?php
// app/Libraries/BladeExtension.php

namespace App\Libraries;

use Illuminate\Pagination\LengthAwarePaginator;
use Jenssegers\Blade\Blade;

class BladeExtension
{
    /**
     * Process view data
     *
     * @param array $data
     * @return array
     */
    public function processData($data)
    {
        $this->processPaginators($data);
        return $this->addErrorsHandler($data);
    }

    /**
     * Process any paginator objects in the data array
     *
     * @param array &$data
     * @return void
     */
    protected function processPaginators(&$data)
    {
        foreach ($data as $key => $value) {
            if ($value instanceof LengthAwarePaginator) {
                // Use the configured theme or override with request-specific theme
                $theme = config('Pagination')->theme ?? 'bootstrap';
                if (isset($data['paginationTheme'])) {
                    $theme = $data['paginationTheme'];
                }
                $renderer = new PaginationRenderer();
                $data[$key]->linksHtml = $renderer->render($value, $theme);
            }
        }
    }

    /**
     * Add Laravel-style errors handler to the data
     *
     * @param array $data
     * @return array
     */
    protected function addErrorsHandler($data)
    {
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
        
        return $data;
    }

    /**
     * Register custom Blade directives
     *
     * @param Blade $blade
     * @return void
     */
    public function registerDirectives($blade)
    {
        // Permission directives
        $blade->directive('can', function ($expression) {
            return "<?php if(can($expression)): ?>";
        });
        $blade->directive('endcan', function () {
            return "<?php endif; ?>";
        });
        $blade->directive('cannot', function ($expression) {
            return "<?php if(cannot($expression)): ?>";
        });
        $blade->directive('endcannot', function () {
            return "<?php endif; ?>";
        });

        // Error handling directive
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
    }
}
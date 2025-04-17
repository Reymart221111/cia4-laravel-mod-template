<?php

namespace App\Libraries\Blade;

use Illuminate\Pagination\LengthAwarePaginator;

class PaginationRenderer
{
    /**
     * @var int Window of links to display around current page
     */
    protected $window;

    /**
     * Render pagination links for a paginator
     *
     * @param LengthAwarePaginator $paginator
     * @param string $theme
     * @return string
     */
    public function render(LengthAwarePaginator $paginator, $theme = 'bootstrap')
    {
        // Load the pagination config
        $paginationConfig = config('Pagination');
        $renderers = $paginationConfig->renderers ?? [];
        $this->window = $paginationConfig->window ?? 3;
        
        // If the theme exists in renderers, use it
        $rendererFunction = $renderers[$theme] ?? null;
        if ($rendererFunction && function_exists($rendererFunction)) {
            return call_user_func($rendererFunction, $paginator, $paginationConfig);
        }
        
        // Use the class methods based on theme
        $method = 'render' . ucfirst($theme);
        if (method_exists($this, $method)) {
            return $this->$method($paginator);
        }
        
        // Fallback to bootstrap theme
        return $this->renderBootstrap($paginator);
    }

    /**
     * Render Bootstrap pagination
     *
     * @param LengthAwarePaginator $paginator
     * @return string
     */
    protected function renderBootstrap(LengthAwarePaginator $paginator)
    {
        $output = '<ul class="pagination">';
        
        // Add navigation links
        $output .= $this->renderPrevButton($paginator, 'bootstrap');
        $output .= $this->renderPageLinks($paginator, 'bootstrap');
        $output .= $this->renderNextButton($paginator, 'bootstrap');
        
        $output .= '</ul>';
        return $output;
    }

    /**
     * Render Tailwind pagination
     *
     * @param LengthAwarePaginator $paginator
     * @return string
     */
    protected function renderTailwind(LengthAwarePaginator $paginator)
    {
        $output = '<nav><ul class="flex items-center -space-x-px h-10 text-base">';
        
        // Add navigation links
        $output .= $this->renderPrevButton($paginator, 'tailwind');
        $output .= $this->renderPageLinks($paginator, 'tailwind');
        $output .= $this->renderNextButton($paginator, 'tailwind');

        $output .= '</ul></nav>';
        return $output;
    }

    /**
     * Render Bulma pagination
     *
     * @param LengthAwarePaginator $paginator
     * @return string
     */
    protected function renderBulma(LengthAwarePaginator $paginator)
    {
        $output = '<nav class="pagination is-centered" role="navigation" aria-label="pagination">';

        // Previous and Next buttons with Bulma classes
        $output .= $this->renderPrevButton($paginator, 'bulma');
        $output .= $this->renderNextButton($paginator, 'bulma');

        // Page list with Bulma styling
        $output .= '<ul class="pagination-list">';
        $output .= $this->renderPageLinks($paginator, 'bulma');
        $output .= '</ul></nav>';
        
        return $output;
    }

    /**
     * Render previous page button
     * 
     * @param LengthAwarePaginator $paginator
     * @param string $theme
     * @return string
     */
    protected function renderPrevButton($paginator, $theme)
    {
        if ($theme === 'bootstrap') {
            if ($paginator->onFirstPage()) {
                return '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
            } else {
                return '<li class="page-item"><a class="page-link" href="' . $paginator->previousPageUrl() . '">&laquo;</a></li>';
            }
        } elseif ($theme === 'tailwind') {
            if ($paginator->onFirstPage()) {
                return '<li><span class="flex items-center justify-center px-4 h-10 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg cursor-not-allowed">&laquo;</span></li>';
            } else {
                return '<li><a href="' . $paginator->previousPageUrl() . '" class="flex items-center justify-center px-4 h-10 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700">&laquo;</a></li>';
            }
        } elseif ($theme === 'bulma') {
            if ($paginator->onFirstPage()) {
                return '<a class="pagination-previous" disabled>&laquo;</a>';
            } else {
                return '<a class="pagination-previous" href="' . $paginator->previousPageUrl() . '">&laquo;</a>';
            }
        }
        
        return '';
    }

    /**
     * Render next page button
     * 
     * @param LengthAwarePaginator $paginator
     * @param string $theme
     * @return string
     */
    protected function renderNextButton($paginator, $theme)
    {
        if ($theme === 'bootstrap') {
            if ($paginator->hasMorePages()) {
                return '<li class="page-item"><a class="page-link" href="' . $paginator->nextPageUrl() . '">&raquo;</a></li>';
            } else {
                return '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
            }
        } elseif ($theme === 'tailwind') {
            if ($paginator->hasMorePages()) {
                return '<li><a href="' . $paginator->nextPageUrl() . '" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700">&raquo;</a></li>';
            } else {
                return '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg cursor-not-allowed">&raquo;</span></li>';
            }
        } elseif ($theme === 'bulma') {
            if ($paginator->hasMorePages()) {
                return '<a class="pagination-next" href="' . $paginator->nextPageUrl() . '">&raquo;</a>';
            } else {
                return '<a class="pagination-next" disabled>&raquo;</a>';
            }
        }
        
        return '';
    }

    /**
     * Render page links
     * 
     * @param LengthAwarePaginator $paginator
     * @param string $theme
     * @return string
     */
    protected function renderPageLinks($paginator, $theme)
    {
        $lastPage = $paginator->lastPage();
        $currentPage = $paginator->currentPage();
        
        if ($currentPage <= $this->window + 2) {
            return $this->renderStartingPages($paginator, $lastPage, $currentPage, $theme);
        } elseif ($currentPage > $lastPage - ($this->window + 2)) {
            return $this->renderEndingPages($paginator, $lastPage, $currentPage, $theme);
        } else {
            return $this->renderMiddlePages($paginator, $lastPage, $currentPage, $theme);
        }
    }

    /**
     * Render starting pages
     * 
     * @param LengthAwarePaginator $paginator
     * @param int $lastPage
     * @param int $currentPage
     * @param string $theme
     * @return string
     */
    protected function renderStartingPages($paginator, $lastPage, $currentPage, $theme)
    {
        $output = '';
        
        // Beginning pages
        for ($i = 1; $i <= min($this->window * 2 + 1, $lastPage); $i++) {
            $output .= $this->renderPageLink($paginator, $i, $currentPage, $theme);
        }

        // Show ellipsis and last page if needed
        if ($lastPage > $this->window * 2 + 1) {
            if ($lastPage > $this->window * 2 + 2) {
                $output .= $this->renderEllipsis($theme);
            }
            $output .= $this->renderPageLink($paginator, $lastPage, $currentPage, $theme);
        }
        
        return $output;
    }

    /**
     * Render ending pages
     * 
     * @param LengthAwarePaginator $paginator
     * @param int $lastPage
     * @param int $currentPage
     * @param string $theme
     * @return string
     */
    protected function renderEndingPages($paginator, $lastPage, $currentPage, $theme)
    {
        $output = $this->renderPageLink($paginator, 1, $currentPage, $theme);

        if ($lastPage - ($this->window * 2) > 2) {
            $output .= $this->renderEllipsis($theme);
        }

        for ($i = max(1, $lastPage - ($this->window * 2)); $i <= $lastPage; $i++) {
            $output .= $this->renderPageLink($paginator, $i, $currentPage, $theme);
        }
        
        return $output;
    }

    /**
     * Render middle pages
     * 
     * @param LengthAwarePaginator $paginator
     * @param int $lastPage
     * @param int $currentPage
     * @param string $theme
     * @return string
     */
    protected function renderMiddlePages($paginator, $lastPage, $currentPage, $theme)
    {
        $output = $this->renderPageLink($paginator, 1, $currentPage, $theme);

        if ($currentPage - $this->window > 2) {
            $output .= $this->renderEllipsis($theme);
        }

        for ($i = max(2, $currentPage - $this->window); $i <= min($lastPage - 1, $currentPage + $this->window); $i++) {
            $output .= $this->renderPageLink($paginator, $i, $currentPage, $theme);
        }

        if ($currentPage + $this->window < $lastPage - 1) {
            $output .= $this->renderEllipsis($theme);
        }

        $output .= $this->renderPageLink($paginator, $lastPage, $currentPage, $theme);
        
        return $output;
    }

    /**
     * Render page link based on theme
     * 
     * @param LengthAwarePaginator $paginator
     * @param int $page
     * @param int $currentPage
     * @param string $theme
     * @return string
     */
    protected function renderPageLink($paginator, $page, $currentPage, $theme)
    {
        if ($theme === 'bootstrap') {
            if ($page == $currentPage) {
                return '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
            } else {
                return '<li class="page-item"><a class="page-link" href="' . $paginator->url($page) . '">' . $page . '</a></li>';
            }
        } elseif ($theme === 'tailwind') {
            if ($page == $currentPage) {
                return '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-blue-600 border border-gray-300 bg-blue-50">' . $page . '</span></li>';
            } else {
                return '<li><a href="' . $paginator->url($page) . '" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700">' . $page . '</a></li>';
            }
        } elseif ($theme === 'bulma') {
            if ($page == $currentPage) {
                return '<li><a class="pagination-link is-current" aria-label="Page ' . $page . '" aria-current="page">' . $page . '</a></li>';
            } else {
                return '<li><a class="pagination-link" href="' . $paginator->url($page) . '" aria-label="Go to page ' . $page . '">' . $page . '</a></li>';
            }
        }
        
        return '';
    }

    /**
     * Render ellipsis based on theme
     * 
     * @param string $theme
     * @return string
     */
    protected function renderEllipsis($theme)
    {
        if ($theme === 'bootstrap') {
            return '<li class="page-item disabled"><span class="page-link">...</span></li>';
        } elseif ($theme === 'tailwind') {
            return '<li><span class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300">...</span></li>';
        } elseif ($theme === 'bulma') {
            return '<li><span class="pagination-ellipsis">&hellip;</span></li>';
        }
        
        return '';
    }
}
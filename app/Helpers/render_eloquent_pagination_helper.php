<?php
// app/Helpers/PaginationHelper.php

/**
 * Render pagination links for Eloquent paginator
 * 
 * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
 * @return string
 */
function render_pagination($paginator)
{
    // Set the current URL as the path
    $paginator->withPath(current_url());
    
    $output = '<ul class="pagination">';
    
    // Previous Page Link
    if ($paginator->onFirstPage()) {
        $output .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    } else {
        $output .= '<li class="page-item"><a class="page-link" href="' . $paginator->previousPageUrl() . '">&laquo;</a></li>';
    }
    
    // Page numbers
    $window = 3; // Number of links on each side of current page
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
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Pagination extends BaseConfig
{
    /**
     * Default theme for pagination
     */
    public $theme = 'bootstrap';
    
    /**
     * Number of links to display on each side of current page
     */
    public $window = 5;
    
    /**
     * Pagination renderers for different CSS frameworks
     */
    public $renderers = [
        'bootstrap' => 'render_pagination_bootstrap',
        'tailwind'  => 'render_pagination_tailwind',
        'bulma'     => 'render_pagination_bulma'
    ];
}
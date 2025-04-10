<?php

if (!function_exists('auth')) {
    /**
     * Get the authentication instance
     *
     * @return \App\Libraries\Authentication
     */
    function auth()
    {
        return \App\Facades\Auth::getInstance();
    }
}
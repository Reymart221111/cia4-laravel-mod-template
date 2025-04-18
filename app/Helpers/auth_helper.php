<?php

/**
 * Get the authentication instance
 *
 * @return \App\Libraries\Authentication\Authentication
 */
if (!function_exists('auth')) {
    function auth()
    {
        return \App\Facades\Auth::getInstance();
    }
}

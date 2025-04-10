<?php
// app/Helpers/authorization_helper.php

use App\Libraries\Gate;

if (!function_exists('gate')) {
    function gate() {
        return Gate::getInstance();
    }
}

if (!function_exists('can')) {
    function can($ability, ...$arguments) {
        return gate()->allows($ability, array_merge([auth()->user()], $arguments));
    }
}

if (!function_exists('cannot')) {
    function cannot($ability, ...$arguments) {
        return gate()->denies($ability, array_merge([auth()->user()], $arguments));
    }
}

if (!function_exists('authorize')) {
    function authorize($ability, ...$arguments) {
        if (cannot($ability, ...$arguments)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Unauthorized action.");
        }
        
        return true;
    }
}
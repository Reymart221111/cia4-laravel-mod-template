<?php

use App\Libraries\Authentication\Gate;

/**
 * Retrieves an instance of the Gate class.
 *
 * @return \App\Libraries\Authentication\Gate The Gate instance.
 */
if (!function_exists('gate')) {
    function gate() {
        return Gate::getInstance();
    }
}

/**
 * Checks if the current user has a specific ability.
 *
 * @param string $ability The ability to check.
 * @param mixed  ...$arguments Additional arguments for the ability check.
 * @return bool True if the user can perform the ability, false otherwise.
 */
if (!function_exists('can')) {
    function can($ability, ...$arguments) {
        return gate()->allows($ability, array_merge([auth()->user()], $arguments));
    }
}

/**
 * Checks if the current user does not have a specific ability.
 *
 * @param string $ability The ability to check.
 * @param mixed  ...$arguments Additional arguments for the ability check.
 * @return bool True if the user cannot perform the ability, false otherwise.
 */
if (!function_exists('cannot')) {
    function cannot($ability, ...$arguments) {
        return gate()->denies($ability, array_merge([auth()->user()], $arguments));
    }
}

/**
 * Authorizes the current user to perform a specific ability.
 * Throws a PageNotFoundException if the user is not authorized.
 *
 * @param string $ability The ability to authorize.
 * @param mixed  ...$arguments Additional arguments for the authorization check.
 * @throws \CodeIgniter\Exceptions\PageNotFoundException If the user is not authorized.
 * @return bool True if the user is authorized.
 */
if (!function_exists('authorize')) {
    function authorize($ability, ...$arguments) {
        if (cannot($ability, ...$arguments)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Unauthorized action.");
        }
        
        return true;
    }
}
<?php

use CodeIgniter\HTTP\IncomingRequest;

/**
 * Get the previous URL from:
 * - POST 'back' param (if POST)
 * - GET 'back' query param
 * - old('back') (if redirected with validation errors)
 * - HTTP_REFERER
 * - fallback route or current URL
 *
 * @param string|null $default
 * @return string
 */
function back_url(?string $default = null): string
{
    $request = service('request');

    // Check POST first if method is POST
    if ($request->getMethod() === 'post') {
        $back = $request->getPost('back');
        if ($back) {
            return $back;
        }
    }

    // Check old input (if redirected back with validation error)
    $back = old('back');
    if ($back) {
        return $back;
    }

    // Check GET param
    $back = $request->getGet('back');
    if ($back) {
        return $back;
    }

    // Check HTTP_REFERER
    $back = $_SERVER['HTTP_REFERER'] ?? null;
    if ($back) {
        return $back;
    }

    // Fallback
    return $default ?? current_url();
}

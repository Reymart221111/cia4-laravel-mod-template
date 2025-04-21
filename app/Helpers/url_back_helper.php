<?php

/**
 * Universal “back” URL helper — no manual query‑strings needed.
 *
 * Priority:
 * 1. POSTed ‘back’ field (from hidden input), no matter the method
 * 2. old('back')       (if redirected back with validation errors)
 * 3. GET ‘back’ param  (if you ever choose to pass it)
 * 4. HTTP_REFERER      (browser header when landing on form)
 * 5. $default uses route_to or current URL so use name routes to make it work
 *
 * @param  string|null  $default  Fallback URL
 * @return string
 */
function back_url(?string $default = null): string
{
    $request = service('request');

    // 1) Always prefer the hidden form field, no matter if PUT/POST/etc
    if ($post = $request->getPost('back')) {
        return $post;
    }

    // 2) If we’ve been redirected back after validation
    if ($old = old('back')) {
        return $old;
    }

    // 3) If a 'back' GET param is present
    if ($get = $request->getGet('back')) {
        return $get;
    }

    // 4) Otherwise, use the HTTP Referer
    if (! empty($_SERVER['HTTP_REFERER'])) {
        return $_SERVER['HTTP_REFERER'];
    }

    // 5) Finally, fallback
    return route_to($default) ?? current_url();
}

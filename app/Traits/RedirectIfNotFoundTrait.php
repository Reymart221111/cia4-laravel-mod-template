<?php

namespace App\Traits;

trait RedirectIfNotFoundTrait
{
    /**
     * Redirects back with error message if resource is not found
     * 
     * @param mixed $resource The resource to check
     * @param string $recordName The resource name to display in message
     * @return mixed|\CodeIgniter\HTTP\RedirectResponse Returns resource or redirect response if not found
     */
    public function redirectIfNotFound($resource, string $recordName = 'Record')
    {
        if (!$resource) {
            $response = redirect()->back()->with('error', "{$recordName} not found");
            $response->send();
            exit;
        }

        return $resource;
    }
}

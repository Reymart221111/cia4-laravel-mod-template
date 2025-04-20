<?php

namespace App\Traits;

use CodeIgniter\Exceptions\PageNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait RedirectIfNotFoundTrait
{
    /**
     * Redirects back to the previous page with error message if the given resource is not found.
     * 
     * @param \Illuminate\Database\Eloquent\Model|null $resource The Eloquent model instance to check for existence
     * @param string $recordName The display name of the resource to use in the error message. Defaults to 'Record'
     * @return \Illuminate\Database\Eloquent\Model|null Returns the resource if found, otherwise redirects and exits
     * @throws \CodeIgniter\HTTP\RedirectResponse If resource is not found, redirects back with error message
     */
    public function redirectBackIfNotFound(?Model $resource, string $recordName = 'Record'): ?Model
    {
        if (!$resource) {
            $response = redirect()->back()->with('error', "{$recordName} not found");
            $response->send();
            exit;
        }

        return $resource;
    }

    /**
     * Redirects back to the previous page with error message if the given resource is not found.
     * 
     * @param \Illuminate\Database\Eloquent\Model|null $resource The Eloquent model instance to check for existence
     * @param string $recordName The display name of the resource to use in the error message. Defaults to 'Record'
     * @return \Illuminate\Database\Eloquent\Model|null|\Illuminate\Database\Eloquent\Collection Returns the resource if found, otherwise redirects and exits
     * @throws \CodeIgniter\Exceptions\PageNotFoundException If resource is not found
     */
    public function redirectBack404IfNotFound(?Model $resource, string $recordName = 'Record'): ?Model
    {
        if (!$resource) {
            throw PageNotFoundException::forPageNotFound("{$recordName} not found");
        }

        return $resource;
    }
}

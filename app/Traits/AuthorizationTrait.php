<?php

namespace App\Traits;

use App\Libraries\Authentication\Gate;

trait AuthorizationTrait
{
    /**
     * Get the Gate instance.
     *
     * @return \App\Libraries\Authentication\Gate
     */
    protected function gate()
    {
        return Gate::getInstance();
    }
    
    /**
     * Determine if the user can perform the given ability.
     *
     * @param string $ability
     * @param mixed ...$arguments
     * @return bool
     */
    protected function can($ability, ...$arguments)
    {
        return $this->gate()->allows($ability, array_merge([auth()->user()], $arguments));
    }
    
    /**
     * Determine if the user cannot perform the given ability.
     *
     * @param string $ability
     * @param mixed ...$arguments
     * @return bool
     */
    protected function cannot($ability, ...$arguments)
    {
        return $this->gate()->denies($ability, array_merge([auth()->user()], $arguments));
    }
    
    /**
     * Authorize the current user for an action.
     * Throws PageNotFoundException if unauthorized.
     *
     * @param string $ability
     * @param mixed ...$arguments
     * @return bool
     * @throws \CodeIgniter\Exceptions\PageNotFoundException
     */
    protected function authorize($ability, ...$arguments)
    {
        if ($this->cannot($ability, ...$arguments)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Unauthorized action.");
        }
        
        return true;
    }
}
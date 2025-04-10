<?php
namespace App\Libraries;

use App\Libraries\Gate;


class AuthServiceProvider
{
    protected $policies = [
       
    ];
    
    public function register()
    {
        $this->registerPolicies();
    }
    
    public function registerPolicies()
    {
        foreach ($this->policies as $model => $policy) {
            gate()->policy($model, $policy);
        }
    }
}
<?php
namespace App\Libraries;

use App\Libraries\Gate;

/**
* AuthServiceProvider
* 
* This class is responsible for registering authorization policies
* throughout the application. It maps model classes to their respective
* policy classes and registers them with the Gate.
*/
class AuthServiceProvider
{
   /**
    * The policy mappings for the application
    * 
    * This array maps model classes to their corresponding policy classes.
    * Example: 'App\Models\User::class => App\Policies\UserPolicy::class'
    * 
    * @var array
    */
   protected $policies = [
      
   ];
   
   /**
    * Register all authentication and authorization services
    * 
    * This method initializes all authorization-related services,
    * including registering policies with the Gate.
    * 
    * @return void
    */
   public function register()
   {
       $this->registerPolicies();
   }
   
   /**
    * Register defined policies with the Gate
    * 
    * This method reads the policy mappings from the $policies property
    * and registers each model-policy pair with the Gate instance.
    * 
    * @return void
    */
   public function registerPolicies()
   {
       foreach ($this->policies as $model => $policy) {
           gate()->policy($model, $policy);
       }
   }
}
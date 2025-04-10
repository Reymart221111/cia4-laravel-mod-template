<?php
namespace App\Libraries;

class Gate
{
    protected static $abilities = [];
    protected static $policies = [];
    protected static $instance;
    
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function define($ability, $callback)
    {
        static::$abilities[$ability] = $callback;
        return $this;
    }
    
    public function policy($model, $policy)
    {
        static::$policies[is_string($model) ? $model : get_class($model)] = $policy;
        return $this;
    }
    
    public function allows($ability, $arguments = [])
    {
        return $this->check($ability, $arguments);
    }
    
    public function denies($ability, $arguments = [])
    {
        return !$this->check($ability, $arguments);
    }
    
    public function check($ability, $arguments = [])
    {
        if (isset(static::$abilities[$ability])) {
            return call_user_func_array(static::$abilities[$ability], $arguments);
        }
        
        if (count($arguments) >= 2) {
            return $this->callPolicyMethod($arguments[0], $ability, array_slice($arguments, 1));
        }
        
        return false;
    }
    
    public function callPolicyMethod($user, $ability, $arguments)
    {
        $instance = $arguments[0] ?? null;
        
        if ($instance) {
            $policy = $this->getPolicyFor($instance);
            
            if ($policy && method_exists($policy, $ability)) {
                return call_user_func_array([$policy, $ability], array_merge([$user], $arguments));
            }
        }
        
        return false;
    }
    
    public function getPolicyFor($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        
        if (isset(static::$policies[$class])) {
            return new static::$policies[$class]();
        }
        
        return null;
    }
}
<?php

namespace App\Libraries\Authentication;

/**
 * Gate
 * 
 * The Gate class provides a simple way to authorize user actions using
 * abilities and policies. It implements the Singleton pattern to ensure
 * a single instance is used throughout the application.
 */
class Gate
{
    /**
     * Registered abilities/permissions with their associated callbacks
     * 
     * @var array
     */
    protected static $abilities = [];

    /**
     * Registered policies for models
     * 
     * @var array
     */
    protected static $policies = [];

    /**
     * The current Gate instance (Singleton)
     * 
     * @var Gate
     */
    protected static $instance;

    /**
     * Get the current Gate instance or create a new one
     * 
     * @return Gate
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Define a new ability/permission with a callback
     * 
     * @param string $ability The name of the ability
     * @param callable $callback Function that determines if the ability is allowed
     * @return $this
     */
    public function define($ability, $callback)
    {
        static::$abilities[$ability] = $callback;
        return $this;
    }

    /**
     * Register a policy for a model
     * 
     * @param string|object $model The model class or object
     * @param string $policy The policy class
     * @return $this
     */
    public function policy($model, $policy)
    {
        static::$policies[is_string($model) ? $model : get_class($model)] = $policy;
        return $this;
    }

    /**
     * Check if an ability is allowed
     * 
     * @param string $ability The ability to check
     * @param array $arguments Arguments to pass to the ability check
     * @return bool
     */
    public function allows($ability, $arguments = [])
    {
        return $this->check($ability, $arguments);
    }

    /**
     * Check if an ability is denied
     * 
     * @param string $ability The ability to check
     * @param array $arguments Arguments to pass to the ability check
     * @return bool
     */
    public function denies($ability, $arguments = [])
    {
        return !$this->check($ability, $arguments);
    }

    /**
     * Check if the given ability is allowed for the arguments
     * 
     * First checks direct ability definitions, then falls back to policy checks
     * 
     * @param string $ability The ability to check
     * @param array $arguments Arguments to pass to the ability check
     * @return bool
     */
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

    /**
     * Call the policy method for a user and ability
     * 
     * @param object $user The user to check permissions for
     * @param string $ability The ability/method name in the policy
     * @param array $arguments Additional arguments to pass to the policy method
     * @return bool|null
     */
    public function callPolicyMethod($user, $ability, $arguments)
    {
        $instance = $arguments[0] ?? null;

        if (!$instance) {
            return false;
        }

        $policy = $this->getPolicyFor($instance);

        if (!$policy) {
            return false;
        }

        // Check for 'before' method first
        if (method_exists($policy, 'before')) {
            $beforeResult = call_user_func_array(
                [$policy, 'before'],
                array_merge([$user, $ability], $arguments)
            );

            // If before returns a boolean value (true/false), return it immediately
            if ($beforeResult !== null) {
                return $beforeResult;
            }
        }

        // If there's no before method or it returned null, check the actual ability
        if (method_exists($policy, $ability)) {
            return call_user_func_array(
                [$policy, $ability],
                array_merge([$user], $arguments)
            );
        }

        return false;
    }

    /**
     * Get the policy instance for the given class
     * 
     * @param string|object $class The model class or object
     * @return object|null The policy instance or null if not found
     */
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

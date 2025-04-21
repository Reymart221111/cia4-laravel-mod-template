<?php

namespace App\Facades;

use App\Libraries\Authentication\Authentication;

/**
 * Facade for the Authentication library.
 *
 * This class provides a static interface to the Authentication library,
 * allowing easy access to authentication-related methods.
 */
class Auth
{
    /**
     * The singleton instance of the Authentication library.
     *
     * @var \App\Libraries\Authentication\Authentication|null
     */
    protected static $instance;

    /**
     * Retrieves the singleton instance of the Authentication library.
     *
     * @return \App\Libraries\Authentication\Authentication The Authentication instance.
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Authentication;
        }
        return self::$instance;
    }

    /**
     * Handles dynamic static method calls to the Authentication library.
     *
     * @param string $method The method name to call.
     * @param array  $args   The arguments to pass to the method.
     * @return mixed The result of the method call.
     */
    public static function __callStatic($method, $args)
    {
        return self::getInstance()->$method(...$args);
    }

    /**
     * Retrieves the currently authenticated user.
     *
     * @return \App\Models\User|null The authenticated user, or null if no user is authenticated.
     */
    public static function user()
    {
        return self::getInstance()->user();
    }

    /**
     * Checks if a user is currently authenticated.
     *
     * @return bool True if a user is authenticated, false otherwise.
     */
    public static function check()
    {
        return self::getInstance()->check();
    }

    /**
     * Attempts to authenticate a user with the given credentials.
     *
     * @param array $credentials The user credentials to authenticate.
     * @return bool True if authentication is successful, false otherwise.
     */
    public static function attempt($credentials)
    {
        return self::getInstance()->attempt($credentials);
    }

    /**
     * Logs in a user.
     *
     * @param \App\Models\User $user The user to log in.
     * @return bool True if the login is successful, false otherwise.
     */
    public static function login($user)
    {
        return self::getInstance()->login($user);
    }

    /**
     * Logs out the currently authenticated user.
     *
     * @return bool True if the logout is successful, false otherwise.
     */
    public static function logout()
    {
        return self::getInstance()->logout();
    }
}
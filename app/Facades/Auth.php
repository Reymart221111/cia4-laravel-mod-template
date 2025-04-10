<?php
namespace App\Facades;

class Auth
{
    protected static $instance;
    
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new \App\Libraries\Authentication();
        }
        return self::$instance;
    }
    
    public static function __callStatic($method, $args)
    {
        return self::getInstance()->$method(...$args);
    }
    
    // Common static methods to expose
    public static function user()
    {
        return self::getInstance()->user();
    }
    
    public static function check()
    {
        return self::getInstance()->check();
    }
    
    public static function attempt($credentials)
    {
        return self::getInstance()->attempt($credentials);
    }
    
    public static function login($user)
    {
        return self::getInstance()->login($user);
    }
    
    public static function logout()
    {
        return self::getInstance()->logout();
    }
}
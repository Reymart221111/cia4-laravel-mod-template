<?php

namespace Config;

use App\Config\Eloquent;
use App\Libraries\LaravelValidator;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function eloquent($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('eloquent');
        }
        return new Eloquent();
    }

    public static function laravelValidator($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('laravelValidator');
        }
        
        return new LaravelValidator();
    }

    public static function authorization($getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('authorization');
        }

        $provider = new \App\Libraries\AuthServiceProvider();
        $provider->register();

        return gate();
    }
}

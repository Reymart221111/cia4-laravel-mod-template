<?php

namespace Config;

use App\Config\Eloquent;
use App\Libraries\Authentication\Gate;
use App\Libraries\Providers\AuthServiceProvider;
use App\Libraries\Validation\LaravelValidator;
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
    public static function eloquent($getShared = true): Eloquent
    {
        if ($getShared) {
            return static::getSharedInstance('eloquent');
        }
        return new Eloquent();
    }

    public static function laravelValidator($getShared = true): LaravelValidator
    {
        if ($getShared) {
            return static::getSharedInstance('laravelValidator');
        }

        return new LaravelValidator();
    }

    public static function authorization($getShared = true): Gate
    {
        if ($getShared) {
            return static::getSharedInstance('authorization');
        }

        $provider = new AuthServiceProvider;
        $provider->register();

        return gate();
    }
}

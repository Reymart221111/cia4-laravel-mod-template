<?php

namespace App\Config;

use Illuminate\Database\Capsule\Manager as Capsule;

class Eloquent
{
    public function __construct()
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'host'      => env('database.default.hostname', 'localhost'),
            'driver'    => env('database.default.DBDriver', 'mysql'),
            'database'  => env('database.default.database', 'ci4'),
            'username'  => env('database.default.username', 'root'),
            'password'  => env('database.default.password', ''),
            'charset'   => env('database.default.DBCharset', 'utf8'),
            'collation' => env('database.default.DBCollat', 'utf8_general_ci'),
            'prefix'    => env('database.default.DBPrefix', ''),
        ]);

        $capsule->setAsGlobal();

        $capsule->bootEloquent();
    }
}

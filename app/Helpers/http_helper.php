<?php
// app/Helpers/http_helper.php

use Illuminate\Http\Client\Factory;

if (!function_exists('http')) {
    function http() {
        return new Factory();
    }
}
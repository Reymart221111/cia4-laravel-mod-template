<?php

namespace App\Traits;

trait HashedPasswordTrait
{
    /**
     * Hash the password before saving it to the database
     *
     * @param string $password
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value && !password_get_info($value)['algo'] ?
            password_hash($value, PASSWORD_DEFAULT) : $value;
    }
}

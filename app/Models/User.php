<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class User extends Model
{
    /**
     * The table associated with the model
     * 
     * @var string
     */
    protected $table = 'users';

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
    ];

    public function setPasswordAttribute($value)
    {
        if ($value && !password_get_info($value)['algo']) {
            $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
        } else {
            $this->attributes['password'] = $value;
        }
    }
}

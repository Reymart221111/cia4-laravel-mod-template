<?php

namespace App\Models;

use App\Traits\HashedPasswordTrait;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HashedPasswordTrait;
    
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
}

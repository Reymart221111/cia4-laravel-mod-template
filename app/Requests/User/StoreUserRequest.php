<?php

namespace App\Requests\User;

use App\Libraries\Validation\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ];
    }
    
    public function messages()
    {
        return [
            // Define your custom messages here (optional)
        ];
    }
    
    public function attributes()
    {
        return [
            // Define your custom attribute names here (optional)
        ];
    }
}
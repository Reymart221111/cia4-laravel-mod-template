<?php

namespace App\Requests\User;

use App\Libraries\Validation\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function rules()
    {
        $id = service('uri')->getSegment(3);
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', Rule::unique('users', 'email')->ignore($id)],
            'password' => 'string|min:6',
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

    public function prepareForValidation()
    {
        $data = $this->getData();

        if(isset($data['password']) && empty($data['password'])){
            unset($data['password']);
        }

        $this->setData($data);
    }
}

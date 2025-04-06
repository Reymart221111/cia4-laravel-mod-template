<?php

namespace App\Libraries;

use App\Exceptions\ValidationException;

abstract class FormRequest
{
    protected $request;
    protected $validator;
    protected $data;
    protected $validationResult;
    
    public function __construct()
    {
        $this->request = \Config\Services::request();
        $this->validator = new LaravelValidator();
        $this->data = $this->request->getPost();
        
        // Automatically validate on instantiation
        $this->validate();
    }
    
    abstract public function rules();
    
    public function messages()
    {
        return [];
    }
    
    public function attributes()
    {
        return [];
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function validate()
    {
        $this->validationResult = $this->validator->validate(
            $this->getData(),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );
        
        return $this->validationResult['success'];
    }
    
    public function fails()
    {
        return !($this->validationResult['success'] ?? false);
    }
    
    public function validated()
    {
        return $this->validationResult['validated'] ?? [];
    }
    
    public function errors()
    {
        return $this->validationResult['errorsByField'] ?? [];
    }
    
    /**
     * Static constructor to create, validate, and automatically redirect on failure
     */
    public static function validateRequest()
    {
        $instance = new static();
        
        if ($instance->fails()) {
            $response = redirect()->back()
                ->withInput()
                ->with('errors', $instance->errors());
            
            throw new ValidationException($response);
        }
        
        return $instance;
    }
}
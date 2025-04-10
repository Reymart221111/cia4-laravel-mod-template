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
        
        // Apply preparation before validation
        $this->prepareForValidation();
        
        // Automatically validate on instantiation
        $this->validate();
    }
    
    abstract public function rules();
    
    /**
     * Prepare the data for validation.
     * Override this method in your request classes to manipulate input data.
     */
    protected function prepareForValidation()
    {
        // This method can be overridden in child classes
        // By default, it does nothing
    }
    
    public function messages()
    {
        return [];
    }
    
    public function attributes()
    {
        return [];
    }
    
    /**
     * Get the validation data
     */
    public function getData()
    {
        return $this->data;
    }
    
    /**
     * Set or modify validation data
     */
    protected function setData(array $data)
    {
        $this->data = $data;
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
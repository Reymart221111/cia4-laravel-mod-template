<?php

namespace App\Libraries;

use App\Exceptions\ValidationException;

/**
 * Abstract FormRequest class for handling form validation
 * 
 * This class provides a structured way to validate form inputs
 * with support for custom rules, messages, and data preparation.
 */
abstract class FormRequest
{
    /**
     * The current HTTP request instance
     * 
     * @var \CodeIgniter\HTTP\IncomingRequest
     */
    protected $request;

    /**
     * The validator instance
     * 
     * @var \App\Libraries\LaravelValidator
     */
    protected $validator;

    /**
     * The data to be validated
     * 
     * @var array
     */
    protected $data;

    /**
     * The result of validation
     * 
     * @var array
     */
    protected $validationResult;

    /**
     * Constructor for FormRequest
     * 
     * Initializes the request, validator, and data
     * Then prepares and validates the data automatically
     */
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

    /**
     * Define validation rules
     * 
     * Child classes must implement this method to specify
     * the validation rules for their form fields
     * 
     * @return array
     */
    abstract public function rules();

    /**
     * Prepare the data for validation
     * 
     * Override this method in child classes to manipulate input data
     * before validation is performed
     * 
     * @return void
     */
    protected function prepareForValidation()
    {
        // This method can be overridden in child classes
        // By default, it does nothing
    }

    /**
     * Define custom error messages for validation rules
     * 
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * Define custom attribute names for validation fields
     * 
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Get the validation data
     * 
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set or modify validation data
     * 
     * @param array $data New data to set
     * @return void
     */
    protected function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Perform validation with current rules and data
     * 
     * @return bool True if validation passes, false otherwise
     */
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

    /**
     * Check if validation has failed
     * 
     * @return bool True if validation failed, false otherwise
     */
    public function fails()
    {
        return !($this->validationResult['success'] ?? false);
    }

    /**
     * Get only the validated data as an object
     * 
     * @param bool $asObject Whether to return as object (true) or array (false)
     * @return mixed Object or array containing validated fields
     */
    public function validated($asObject = false)
    {
        $data = $this->validationResult['validated'] ?? [];
        return $asObject ? (object) $data : $data;
    }

    /**
     * Get validation errors by field
     * 
     * @return array Array of error messages keyed by field name
     */
    public function errors()
    {
        return $this->validationResult['errorsByField'] ?? [];
    }

    /**
     * Static constructor to create, validate, and automatically redirect on failure
     * 
     * This method creates an instance of the form request,
     * validates it, and throws an exception if validation fails
     * 
     * @throws ValidationException When validation fails
     * @return static The validated form request instance
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

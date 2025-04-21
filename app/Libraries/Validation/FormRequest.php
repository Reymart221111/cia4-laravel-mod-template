<?php

namespace App\Libraries\Validation;

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
     * @var \App\Libraries\Validation\LaravelValidator
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
     * Holds the successfully validated data subset.
     * Populated after successful validation.
     * @var array|null
     */
    protected ?array $validatedData = null;

    /**
     * Constructor for FormRequest
     * 
     * Initializes the request, validator, and data
     * Then prepares and validates the data automatically
     */
    public function __construct()
    {
        $this->request = \Config\Services::request();
        $this->validator = service('laravelValidator');
        $this->data = $this->request->getPost();

        // Combine POST data and FILES
        $this->data = array_merge($this->request->getPost(), $this->collectFiles());

        // --- Preparation ---
        $this->prepareForValidation();

        $this->validate();

        $this->handleValidationFailure();
    }

    protected function handleValidationFailure()
    {
        if ($this->fails()) {
            $response = redirect()->back()
                ->withInput()
                ->with('errors', $this->errors());
            $response->send();
            exit();
        }
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
     * Collect files from the request and format them for validation
     * 
     * This method processes uploaded files and formats them into a structure
     * compatible with Laravel's validation system.
     * 
     * @return array Array of processed files
     */
    protected function collectFiles()
    {
        $files = [];
        $uploadedFiles = $this->request->getFiles();

        if (empty($uploadedFiles)) {
            return $files;
        }

        foreach ($uploadedFiles as $fieldName => $fileInfo) {
            if (is_array($fileInfo)) {
                $files[$fieldName] = $this->processMultipleFiles($fileInfo);
            } else {
                $files[$fieldName] = $this->processSingleFile($fileInfo);
            }
        }

        return $files;
    }

    /**
     * Process multiple files from the same input
     * 
     * @param array $fileInfoArray Array of uploaded files
     * @return array Processed files in Laravel-compatible format
     */
    private function processMultipleFiles(array $fileInfoArray)
    {
        $processedFiles = [];

        foreach ($fileInfoArray as $key => $file) {
            if ($file->isValid()) {
                $processedFiles[$key] = $this->formatFileData($file);
            }
        }

        return $processedFiles;
    }

    /**
     * Process a single file upload
     * 
     * @param object $file The uploaded file object
     * @return array|null File in Laravel-compatible format or null if invalid
     */
    private function processSingleFile($file)
    {
        return $file->isValid() ? $this->formatFileData($file) : null;
    }

    /**
     * Format file data into Laravel-compatible structure
     * 
     * @param object $file The uploaded file object
     * @return array Formatted file data
     */
    private function formatFileData($file)
    {
        return [
            'name' => $file->getName(),
            'type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'tmp_name' => $file->getTempName(),
            'error' => 0,
            '_ci_file' => $file // Store the original file for later use
        ];
    }

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

        // *** Store validated data on success ***
        if ($this->validationResult['success']) {
            $this->validatedData = $this->validationResult['validated'] ?? [];
        } else {
            $this->validatedData = null; // Ensure it's null on failure
        }

        return $this->validationResult['success'];
    }

    /**
     * Check if validation has failed
     *
     * @return bool True if validation failed, false otherwise
     */
    public function fails()
    {
        // Ensure validation has run if checking failure status directly
        // (Though constructor runs it, this makes fails() safer if called independently)
        if (!isset($this->validationResult)) {
            $this->validate();
        }
        return !($this->validationResult['success'] ?? false);
    }


    /**
     * Get the validated data subset.
     * Returns an empty array if validation failed or data is not available.
     * (Keeping this defensive return is good practice, although the constructor
     * exit should prevent this path in the main controller flow)
     *
     * @param bool $asObject Whether to return as object (true) or array (false)
     * @return array|\stdClass Returns validated data as array/stdClass, or empty array/stdClass on failure.
     */
    public function validated(bool $asObject = false): array|\stdClass
    {
        // Fails check is technically redundant if constructor exits, but harmless
        if ($this->fails() || is_null($this->validatedData)) {
            return $asObject ? new \stdClass() : [];
        }
        return $asObject ? (object) $this->validatedData : $this->validatedData;
    }

    /**
     * Get validation errors by field
     *
     * @return array Array of error messages keyed by field name
     */
    public function errors()
    {
        // Ensure validation has run
        if (!isset($this->validationResult)) {
            $this->validate();
        }
        return $this->validationResult['errorsByField'] ?? [];
    }


    /**
     * Magic method to access validated data as properties.
     *
     * Example: $request->name retrieves the validated 'name' field.
     * Returns null if validation failed or the key doesn't exist in validated data.
     *
     * @param string $key The property name (field key)
     * @return mixed|null The value of the validated field or null
     */
    public function __get(string $key): mixed
    {
        // Ensure validation has run successfully and data is available
        if ($this->fails() || is_null($this->validatedData)) {
            // Trigger warning for accessing before validation or on failure?
            // trigger_error("Attempting to access property '{$key}' before successful validation or on validation failure.", E_USER_WARNING);
            return null;
        }

        return $this->validatedData[$key] ?? null;
    }

    /**
     * Magic method isset check for validated data properties.
     *
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        if ($this->fails() || is_null($this->validatedData)) {
            return false;
        }
        return isset($this->validatedData[$key]);
    }

    /**
     * Check if the request contains a non-empty value for the given field
     *
     * @param string $key The field name to check
     * @return bool True if the field exists and has a non-empty value
     */
    public function has(string $key): bool
    {
        if (!$this->fails() && !is_null($this->validatedData)) {
            return isset($this->validatedData[$key]) &&
                $this->validatedData[$key] !== '' &&
                $this->validatedData[$key] !== null;
        }

        return isset($this->data[$key]) &&
            $this->data[$key] !== '' &&
            $this->data[$key] !== null;
    }

    /**
     * Check if the request contains a file for the given field
     *
     * @param string $key The file field name to check
     * @return bool True if a valid file exists for the field
     */
    public function hasFile(string $key): bool
    {
        if (!isset($this->data[$key])) {
            return false;
        }

        $file = $this->data[$key];

        if (is_array($file) && isset($file['_ci_file'])) {
            return true;
        }

        if (is_array($file)) {
            foreach ($file as $singleFile) {
                if (is_array($singleFile) && isset($singleFile['_ci_file'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the file instance for the given field
     *
     * @param string $key The file field name
     * @return mixed|null The file instance or null if not found
     */
    public function file(string $key)
    {
        if (!$this->hasFile($key)) {
            return null;
        }

        $file = $this->data[$key];

        if (is_array($file) && isset($file['_ci_file'])) {
            return $file['_ci_file'];
        }

        if (is_array($file)) {
            $files = [];
            foreach ($file as $index => $singleFile) {
                if (is_array($singleFile) && isset($singleFile['_ci_file'])) {
                    $files[$index] = $singleFile['_ci_file'];
                }
            }
            return !empty($files) ? $files : null;
        }

        return null;
    }

    /**
     * Get validation errors as a chainable collection
     *
     * @return ErrorsCollection Collection object with error messages
     */
    public function getErrors(): ErrorsCollection
    {
        if (!isset($this->validationResult)) {
            $this->validate();
        }

        return new ErrorsCollection($this->validationResult['errorsByField'] ?? []);
    }

    /**
     * Static constructor to create, validate, and automatically redirect on failure
     * 
     * This method creates an instance of the form request,
     * validates it, and throws an exception if validation fails
     * 
     * @param bool $asArray When true, return validated array; when false, return the FormRequest instance
     */
    public static function validateRequest(bool $asArray = true): array|static
    {
        $instance = new static();

        if ($instance->fails()) {
            $response = redirect()->back()
                ->withInput()
                ->with('errors', $instance->errors());

            throw new ValidationException($response);
        }

        return $asArray
            ? $instance->validated()
            : $instance;
    }
}

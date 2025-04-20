<?php

namespace App\Libraries\Validation;

class ValidatedData
{
    /**
     * The validated data
     *
     * @var array
     */
    private $data;

    /**
     * Constructor
     *
     * @param array $data The validated data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get all validated data
     *
     * @param bool $asObject Whether to return as object (true) or array (false)
     * @return mixed Validated data as object or array
     */
    public function validated($asObject = false)
    {
        return $asObject ? (object) $this->data : $this->data;
    }

    /**
     * Get all validated data
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Get only specified keys from validated data
     *
     * @param string|array $keys The keys to get
     * @return array
     */
    public function only($keys)
    {
        if (is_string($keys)) {
            $keys = func_get_args();
        }

        return array_intersect_key($this->data, array_flip((array) $keys));
    }

    /**
     * Get all validated data except specified keys
     *
     * @param string|array $keys The keys to exclude
     * @return array
     */
    public function except($keys)
    {
        if (is_string($keys)) {
            $keys = func_get_args();
        }

        return array_diff_key($this->data, array_flip((array) $keys));
    }

    /**
     * Get a specific field from validated data
     *
     * @param string $key The key to get
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if the validated data contains a non-empty value for the given field
     *
     * @param string $key The field name to check
     * @return bool True if the field exists and has a non-empty value
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]) &&
            $this->data[$key] !== '' &&
            $this->data[$key] !== null;
    }

    /**
     * Check if the validated data contains a file for the given field
     *
     * @param string $key The file field name to check
     * @return bool True if a valid file exists for the field
     */
    public function hasFile(string $key): bool
    {
        // Check if the field exists in the data
        if (!isset($this->data[$key])) {
            return false;
        }

        $file = $this->data[$key];

        // Check for single file
        if (is_array($file) && isset($file['_ci_file'])) {
            return true;
        }

        // Check for multiple files
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

        // Return single file
        if (is_array($file) && isset($file['_ci_file'])) {
            return $file['_ci_file'];
        }

        // Return array of files for multiple file uploads
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
     * Get validation errors collection
     *
     * @return ErrorsCollection
     */
    public function getErrors(): ErrorsCollection
    {
        return new ErrorsCollection([]);
    }

    /**
     * Magic method to access data as properties
     *
     * @param string $name Property name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Check if key exists in data
     *
     * @param string $name Property name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
}

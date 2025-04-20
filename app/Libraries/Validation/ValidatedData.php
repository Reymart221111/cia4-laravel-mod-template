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

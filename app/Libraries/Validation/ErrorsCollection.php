<?php

namespace App\Libraries\Validation;

/**
 * Class for managing validation error messages with chainable methods
 */
class ErrorsCollection
{
    /**
     * The error messages
     *
     * @var array
     */
    protected $errors;

    /**
     * Constructor
     *
     * @param array $errors Error messages keyed by field name
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * Get all error messages
     *
     * @return array
     */
    public function all(): array
    {
        return $this->errors;
    }

    /**
     * Get the first error message for a field
     * If no field is specified, returns the first error message from any field
     *
     * @param string|null $field Field name (optional)
     * @return string|null First error message or null if no errors
     */
    public function first(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? null;
        }

        // If no field specified, get the first error from any field
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return is_array($fieldErrors) ? $fieldErrors[0] : $fieldErrors;
            }
        }

        return null;
    }

    /**
     * Get all error messages for a specific field
     *
     * @param string $field Field name
     * @return array Error messages for the field
     */
    public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if there are any error messages
     *
     * @return bool
     */
    public function hasAny(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if a specific field has error messages
     *
     * @param string $field Field name
     * @return bool
     */
    public function has(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }

    /**
     * Count the total number of error messages
     *
     * @return int
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->errors as $fieldErrors) {
            $count += is_array($fieldErrors) ? count($fieldErrors) : 1;
        }
        return $count;
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
}

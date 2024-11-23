<?php

namespace Php22\Utils;

class Validator
{
    private $errors = [];

    /**
     * Validate that a field is not empty.
     *
     * @param string $field
     * @param string $value
     * @param string $message
     * @return void
     */
    public function required(string $field, ?string $value, string $message = 'This field is required.')
    {
        if (empty($value)) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Get all validation errors.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Check if the validation passed.
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }
}

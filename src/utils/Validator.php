<?php

namespace Php22\Utils;

class Validator
{
    private $errors = [];

    private $errorsKey = 'errors';

    /**
     * Validate a single field is not empty.
     *
     * @param string $field
     * @param string|null $value
     * @param string $message
     */
    public function required(string $field, ?string $value, string $message = 'This field is required.')
    {
        if (empty($value)) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Validate multiple fields using rules.
     *
     * @param array $fields Array of fields and rules. Example: ['username' => 'required', 'password' => 'required']
     * @param array $data The input data to validate.
     */
    public function validate(array $fields, array $data)
    {
        foreach ($fields as $field => $rule) {
            if ($rule === 'required') {
                $this->required($field, $data[$field] ?? null, ucfirst($field) . ' is required.');
            }
            // Add more validation rules as needed (e.g., email, min, max, etc.)
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
     * Check if validation passed.
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Flass errors to an an array
     */
    public function flashErrors()
    {
        $array = array();

        foreach ($this->errors() as $field => $error) {
            $array[] = $error;
        }

        Flash::set($this->errorsKey, $array);
    }
}

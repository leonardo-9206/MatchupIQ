<?php
/**
 * Validator
 * Centralized request input validation and sanitization service.
 * Decouples input validation logic from controllers, ensuring DRY and standardized error responses.
 */

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    private function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    /**
     * Factory method to instantiate and run validation.
     *
     * @param array $data Input data (e.g. from JSON body or $_GET/$_POST)
     * @param array $rules Associative array of field => rules (e.g. 'email' => 'required|email')
     * @return self
     */
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    /**
     * Checks if validation failed.
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Returns associative array of validation error messages.
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Returns sanitized data containing only fields defined in rules.
     */
    public function validated(): array
    {
        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $validated[$field] = is_string($this->data[$field]) ? trim($this->data[$field]) : $this->data[$field];
            }
        }
        return $validated;
    }

    /**
     * Evaluates all specified validation rules against input data.
     */
    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $ruleList = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            $isNumericField = in_array('numeric', $ruleList, true) || in_array('integer', $ruleList, true);

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                // If field is optional and not present or empty string, skip subsequent non-required rules
                $isRequired = in_array('required', $ruleList, true);
                if (!$isRequired && ($value === null || $value === '')) {
                    continue;
                }

                switch ($ruleName) {
                    case 'required':
                        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                            $this->addError($field, "The {$field} field is required.");
                        }
                        break;

                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, "The {$field} must be a valid email address.");
                        }
                        break;

                    case 'min':
                        $min = (float) ($params[0] ?? 0);
                        if ($isNumericField && is_numeric($value)) {
                            if ((float) $value < $min) {
                                $this->addError($field, "The {$field} must be at least {$min}.");
                            }
                        } else {
                            $strVal = is_string($value) ? trim($value) : (string) $value;
                            if (mb_strlen($strVal) < (int) $min) {
                                $this->addError($field, "The {$field} must be at least " . (int) $min . " characters.");
                            }
                        }
                        break;

                    case 'max':
                        $max = (float) ($params[0] ?? 0);
                        if ($isNumericField && is_numeric($value)) {
                            if ((float) $value > $max) {
                                $this->addError($field, "The {$field} may not be greater than {$max}.");
                            }
                        } else {
                            $strVal = is_string($value) ? trim($value) : (string) $value;
                            if (mb_strlen($strVal) > (int) $max) {
                                $this->addError($field, "The {$field} may not be greater than " . (int) $max . " characters.");
                            }
                        }
                        break;

                    case 'numeric':
                        if (!is_numeric($value)) {
                            $this->addError($field, "The {$field} must be a numeric value.");
                        }
                        break;

                    case 'integer':
                        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $this->addError($field, "The {$field} must be an integer.");
                        }
                        break;

                    case 'in':
                        if (!in_array((string) $value, $params, true)) {
                            $allowed = implode(', ', $params);
                            $this->addError($field, "The {$field} must be one of: {$allowed}.");
                        }
                        break;
                }

                // If error registered for this field, break rule chain for the same field
                if (isset($this->errors[$field])) {
                    break;
                }
            }
        }
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }
}

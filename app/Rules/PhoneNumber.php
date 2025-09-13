<?php

namespace App\Rules;

use App\Helpers\PhoneValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    private string $region;

    public function __construct(string $region = 'TR')
    {
        $this->region = $region;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $result = PhoneValidator::validate($value, $this->region);

        if (!$result['valid']) {
            $fail("The {$attribute} must be a valid phone number. " . ($result['error'] ?? ''));
        }
    }
}

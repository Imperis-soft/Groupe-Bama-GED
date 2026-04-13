<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidMetadata implements Rule
{
    public function __construct()
    {
    }

    public function passes($attribute, $value)
    {
        if (empty($value)) return true;

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) return false;

        // Ensure top-level is an array/object
        if (! is_array($decoded)) return false;

        // Validate values are scalar or arrays of scalars
        foreach ($decoded as $k => $v) {
            if (is_scalar($v) || is_null($v)) continue;
            if (is_array($v)) {
                foreach ($v as $item) {
                    if (! is_scalar($item) && ! is_null($item)) return false;
                }
                continue;
            }
            return false;
        }

        return true;
    }

    public function message()
    {
        return 'La valeur de metadata doit être un JSON valide et contenir uniquement des valeurs scalaires ou des tableaux de scalaires.';
    }
}

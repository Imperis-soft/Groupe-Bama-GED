<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidApprovalWorkflow implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Essayer de décoder le JSON
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $fail('Le workflow d\'approbation doit être un JSON valide.');
            return;
        }

        // Vérifier que c'est un tableau
        if (!is_array($decoded)) {
            $fail('Le workflow d\'approbation doit être un tableau JSON.');
            return;
        }

        // Vérifier que chaque élément est une chaîne non vide
        foreach ($decoded as $step) {
            if (!is_string($step) || empty(trim($step))) {
                $fail('Chaque étape du workflow doit être une chaîne non vide.');
                return;
            }
        }

        // Vérifier qu'il y a au moins une étape
        if (empty($decoded)) {
            $fail('Le workflow d\'approbation doit contenir au moins une étape.');
            return;
        }
    }
}
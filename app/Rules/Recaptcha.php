<?php

namespace App\Rules;

use App\Services\Auth\RecaptchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Recaptcha implements ValidationRule
{
    protected string $action;

    public function __construct(string $action = 'login')
    {
        $this->action = $action;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Si le service n'est pas activé via la config, on valide automatiquement
        /** @var RecaptchaService $recaptchaService */
        $recaptchaService = app(RecaptchaService::class);
        
        if (!$recaptchaService->isEnabled()) {
            return;
        }

        if (empty($value) || !is_string($value)) {
            $fail('Le CAPTCHA est invalide ou manquant.');
            return;
        }

        if (!$recaptchaService->verify($value, $this->action)) {
            $fail('La vérification CAPTCHA a échoué. Veuillez rafraîchir la page et réessayer.');
        }
    }
}

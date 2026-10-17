<?php

namespace App\Http\Requests\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $telephone = preg_replace('/[\s.\-()]/', '', (string) $this->input('telephone'));

        if (preg_match('/^(?:\+235|00235|235)?(\d{8})$/', $telephone, $m)) {
            $telephone = '+235'.$m[1];
        }

        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'telephone' => $telephone,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'telephone' => ['required', 'regex:/^\+235\d{8}$/', Rule::unique(User::class, 'telephone')],
            // Liste blanche : on ne peut jamais s'inscrire admin ou modérateur
            'type_compte' => ['required', Rule::in([Role::Client->value, Role::Bailleur->value])],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.regex' => 'Entrez un numéro tchadien valide (8 chiffres).',
            'telephone.unique' => 'Ce numéro est déjà utilisé.',
            'email.unique' => 'Cet e-mail est déjà utilisé.',
            'type_compte.in' => 'Type de compte invalide.',
        ];
    }
}

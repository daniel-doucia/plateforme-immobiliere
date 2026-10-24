<?php

namespace App\Http\Requests\Bailleur;

use App\Enums\Role;
use App\Enums\TypeBailleur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Bailleur->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TypeBailleur::class)],
            'nom_commercial' => ['nullable', 'required_if:type,agence', 'string', 'max:255'],
            'piece_identite' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_commercial.required_if' => 'Le nom de l\'agence ou de l\'hôtel est obligatoire.',
            'piece_identite.mimes' => 'La pièce doit être une image (JPG, PNG) ou un PDF.',
            'piece_identite.max' => 'La pièce ne doit pas dépasser 4 Mo.',
        ];
    }
}

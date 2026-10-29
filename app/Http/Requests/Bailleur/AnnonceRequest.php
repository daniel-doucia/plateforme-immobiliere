<?php

namespace App\Http\Requests\Bailleur;

use App\Enums\Periodicite;
use App\Enums\TypeBien;
use App\Enums\TypeOffre;
use App\Models\Annonce;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnonceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $annonce = $this->route('annonce');

        return $annonce
            ? $this->user()->can('update', $annonce)
            : $this->user()->can('create', Annonce::class);
    }

    public function rules(): array
    {
        return [
            'quartier_id' => ['required', 'integer', Rule::exists('quartiers', 'id')],
            'titre' => ['required', 'string', 'min:10', 'max:120'],
            'description' => ['required', 'string', 'min:30', 'max:5000'],
            'type_bien' => ['required', Rule::enum(TypeBien::class)],
            'type_offre' => ['required', Rule::enum(TypeOffre::class)],
            'prix' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'periodicite' => ['nullable', 'required_if:type_offre,location', Rule::enum(Periodicite::class)],
            'caution' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'superficie' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'nb_pieces' => ['nullable', 'integer', 'between:0,50'],
            'nb_chambres' => ['nullable', 'integer', 'between:0,50'],
            'nb_salles_bain' => ['nullable', 'integer', 'between:0,50'],
            'adresse_indicative' => ['nullable', 'string', 'max:255'],

            // Détails propres aux hôtels : seules les valeurs des listes officielles sont acceptées
            'details_hotel' => ['nullable', 'array'],
            'details_hotel.etoiles' => ['nullable', 'integer', 'between:0,5'],
            'details_hotel.types_chambre' => ['nullable', 'array', 'max:'.count(config('hotel.types_chambre'))],
            'details_hotel.types_chambre.*' => ['string', Rule::in(array_keys(config('hotel.types_chambre')))],
            'details_hotel.equipements' => ['nullable', 'array', 'max:'.count(config('hotel.equipements'))],
            'details_hotel.equipements.*' => ['string', Rule::in(array_keys(config('hotel.equipements')))],
            'details_hotel.paiements' => ['nullable', 'array', 'max:'.count(config('hotel.paiements'))],
            'details_hotel.paiements.*' => ['string', Rule::in(array_keys(config('hotel.paiements')))],
            'details_hotel.arrivee' => ['nullable', 'date_format:H:i'],
            'details_hotel.depart' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'quartier_id.required' => 'Choisissez un quartier.',
            'titre.min' => 'Le titre doit contenir au moins 10 caractères.',
            'description.min' => 'La description doit contenir au moins 30 caractères.',
            'prix.min' => 'Indiquez un prix valide.',
            'periodicite.required_if' => 'Indiquez si le prix est par mois ou par an.',
            'details_hotel.etoiles.between' => 'Le classement doit être compris entre 0 et 5 étoiles.',
            'details_hotel.arrivee.date_format' => 'Indiquez l\'heure d\'arrivée au format HH:MM.',
            'details_hotel.depart.date_format' => 'Indiquez l\'heure de départ au format HH:MM.',
        ];
    }
}

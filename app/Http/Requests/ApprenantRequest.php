<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\VerifiePerimetre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprenantRequest extends FormRequest
{
    use VerifiePerimetre;

    public function authorize(): bool
    {
        if ($apprenant = $this->route('apprenant')) {
            $this->exigerPerimetre($apprenant->structure, 'apprenants', $apprenant->id);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'prenom' => ['required', 'string', 'max:100'],
            'nom' => ['required', 'string', 'max:100'],
            'sexe' => ['required', Rule::in(['M', 'F'])],
            'date_naissance' => ['required', 'date', 'before:today', 'after:-25 years'],
            'lieu_naissance' => ['nullable', 'string', 'max:150'],
            'situation' => ['required', Rule::in(['Descolarise', 'Non_scolarise'])],
            'derniere_classe' => ['nullable', 'string', 'max:100'],
            'cause_descolarisation' => ['nullable', 'string', 'max:2000'],
            'region' => ['nullable', 'string', 'max:100'],
            'departement' => ['nullable', 'string', 'max:100'],
            'commune' => ['nullable', 'string', 'max:100'],
            'nom_parent' => ['nullable', 'string', 'max:150'],
            'telephone_parent' => ['nullable', 'string', 'max:20', 'regex:/^[0-9 +]{7,20}$/'],
            'handicap' => ['required', Rule::in(['oui', 'non'])],
            'situation_familiale' => ['nullable', 'string', 'max:150'],
            'structure_id' => ['required', 'integer', Rule::exists('structures', 'id')->whereNull('deleted_at')],
            'classe_id' => ['nullable', 'integer', Rule::exists('classes', 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'date_naissance' => 'date de naissance', 'lieu_naissance' => 'lieu de naissance', 'derniere_classe' => 'dernière classe',
            'cause_descolarisation' => 'cause de déscolarisation', 'nom_parent' => 'nom du parent', 'telephone_parent' => 'téléphone du parent',
            'situation_familiale' => 'situation familiale', 'structure_id' => 'structure', 'classe_id' => 'classe',
        ];
    }

    public function messages(): array
    {
        return ['telephone_parent.regex' => 'Le téléphone ne doit contenir que des chiffres, des espaces et le signe +.'];
    }
}

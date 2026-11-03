<?php

namespace App\Http\Requests;

use App\Models\Structure;
use App\Services\StructureStateMachine;
use App\Http\Requests\Concerns\VerifiePerimetre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validation des champs d'une structure. Le code n'est jamais accepté. */
class StructureRequest extends FormRequest
{
    use VerifiePerimetre;

    public function authorize(): bool
    {
        // Les droits sont vérifiés par le filtre « permission » ; ici, le périmètre en modification.
        if ($id = $this->route('id')) {
            $this->exigerPerimetre(Structure::find($id), 'structures', $id);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'nom_structure' => ['required', 'string', 'max:255'],
            'type_offre_id' => ['required', 'integer', Rule::exists('types_offre', 'id')->where('actif', 1)],
            'region' => ['required', 'string', 'max:255'],
            'departement' => ['required', 'string', 'max:255'],
            'commune' => ['required', 'string', 'max:255'],
            'quartier' => ['required', 'string', 'max:255'],
            'ia' => ['required', 'string', 'max:255'],
            'ief' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:12,17'],
            'longitude' => ['nullable', 'numeric', 'between:-18,-11'],
            'langue_nationale' => ['required', 'string', 'max:255'],
            'operateur_id' => ['nullable', 'integer', Rule::exists('operateur', 'id')],
            'etat' => ['required', Rule::in(Structure::ETATS)],
        ];
    }

    public function attributes(): array
    {
        return [
            'nom_structure' => 'nom de la structure', 'type_offre_id' => "type d'offre", 'region' => 'région',
            'departement' => 'département', 'ia' => 'IA', 'ief' => 'IEF', 'langue_nationale' => 'langue nationale',
            'operateur_id' => 'opérateur', 'etat' => 'état',
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'La latitude doit se situer au Sénégal (entre 12 et 17).',
            'longitude.between' => 'La longitude doit se situer au Sénégal (entre -18 et -11).',
        ];
    }

    /** Les champs que le formulaire peut modifier : le code et l'archivage en sont exclus. */
    public function donnees(): array
    {
        return $this->safe()->only((new Structure)->getFillable());
    }

    public static function libelleEtat(string $etat): string
    {
        return StructureStateMachine::LIBELLES[$etat] ?? $etat;
    }
}

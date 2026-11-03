<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class TypeOffre extends Model
{
    use Auditable;

    protected $table = 'types_offre';

    protected $fillable = ['libelle', 'code', 'prefixe', 'description', 'actif'];

    /** Dernier chiffre du code : suffixe des codes de structure de ce type. */
    public function suffixe(): ?string
    {
        $dernier = substr(trim((string) $this->code), -1);

        return ctype_digit($dernier) ? $dernier : null;
    }
}

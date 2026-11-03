<?php

namespace Tests;

use App\Models\Structure;
use App\Models\User;
use App\Services\StructureCodeService;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function referentiels(): void
    {
        $this->seed(ReferenceSeeder::class);
    }

    protected function utilisateur(string $niveau = 'NATIONAL', array $attributs = [], int $role = 1): User
    {
        return User::create($attributs + [
            'nom' => 'Test', 'prenom' => $niveau, 'email' => strtolower($niveau).uniqid().'@exemple.test',
            'password' => 'MotDePasse2026', 'role_id' => $role, 'status' => true, 'niveau_acces' => $niveau,
        ]);
    }

    protected function structure(string $ia, string $ief, array $attributs = []): Structure
    {
        $s = new Structure($attributs + [
            'nom_structure' => "Structure {$ief}", 'type_offre_id' => 2, 'region' => 'DAKAR', 'departement' => 'Pikine',
            'commune' => 'Thiaroye', 'quartier' => 'Centre', 'ia' => $ia, 'ief' => $ief, 'langue_nationale' => 'Wolof', 'etat' => 'EN_ATTENTE',
        ]);
        $s->code_structure = app(StructureCodeService::class)->genererCode((int) $s->type_offre_id);
        $s->save();

        return $s;
    }
}

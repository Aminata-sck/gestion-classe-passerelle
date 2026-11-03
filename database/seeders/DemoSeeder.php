<?php

namespace Database\Seeders;

use App\Models\Apprenant;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\Facilitateur;
use App\Models\Operateur;
use App\Models\Structure;
use App\Models\User;
use App\Services\StructureCodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Jeu de démonstration entièrement fictif (captures d'écran, essais, soutenance).
 * Ne jamais exécuter sur la base de production.
 */
class DemoSeeder extends Seeder
{
    public const MOT_DE_PASSE = 'Demo#2026passe';

    public function run(StructureCodeService $codes): void
    {
        $op1 = Operateur::create(['code_operateur' => 'OP-001', 'nom_organisation' => 'Association Jàng ak Jëf', 'type_operateur' => 'Association', 'prenom_responsable' => 'Awa', 'nom_responsable' => 'Ndiaye', 'telephone' => '77 000 00 01', 'date_debut' => '2025-10-01', 'statut' => 'actif']);
        $op2 = Operateur::create(['code_operateur' => 'OP-002', 'nom_organisation' => 'ONG Lire pour Grandir', 'type_operateur' => 'ONG', 'prenom_responsable' => 'Moussa', 'nom_responsable' => 'Fall', 'telephone' => '77 000 00 02', 'date_debut' => '2025-10-01', 'statut' => 'actif']);

        $s = fn (array $d, int $type) => tap(new Structure($d + ['type_offre_id' => $type, 'departement' => $d['departement'] ?? $d['commune'], 'langue_nationale' => $d['langue_nationale'] ?? 'Wolof']), function ($x) use ($codes, $type) {
            $x->code_structure = $codes->genererCode($type);
            $x->save();
        });
        $pk1 = $s(['nom_structure' => 'Classe passerelle de Thiaroye', 'region' => 'DAKAR', 'commune' => 'Thiaroye Gare', 'quartier' => 'Thiaroye', 'ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine', 'operateur_id' => $op1->id, 'etat' => 'OUVERT', 'latitude' => 14.7486, 'longitude' => -17.3803], 2);
        $pk2 = $s(['nom_structure' => 'Classe passerelle de Guinaw Rails', 'region' => 'DAKAR', 'commune' => 'Guinaw Rails Nord', 'quartier' => 'Guinaw Rails', 'ia' => 'IA Pikine Guediawaye', 'ief' => 'IEF Pikine', 'operateur_id' => $op1->id, 'etat' => 'VALIDE'], 2);
        $mb1 = $s(['nom_structure' => 'Classe passerelle de Grand Mbour', 'region' => 'THIES', 'commune' => 'Mbour', 'quartier' => 'Grand Mbour', 'ia' => 'IA THIES', 'ief' => 'IEF Mbour 1', 'operateur_id' => $op2->id, 'etat' => 'OUVERT', 'langue_nationale' => 'Sérère'], 2);
        $s(['nom_structure' => 'Daara communautaire de Saly', 'region' => 'THIES', 'commune' => 'Saly Portudal', 'quartier' => 'Saly', 'ia' => 'IA THIES', 'ief' => 'IEF Mbour 1', 'operateur_id' => $op2->id, 'etat' => 'EN_ATTENTE'], 4);
        $sl = $s(['nom_structure' => 'École communautaire de Guet Ndar', 'region' => 'SAINT-LOUIS', 'commune' => 'Saint-Louis', 'quartier' => 'Guet Ndar', 'ia' => 'IA Saint-Louis', 'ief' => 'IEF Saint-Louis Commune', 'operateur_id' => $op2->id, 'etat' => 'OUVERT', 'langue_nationale' => 'Wolof'], 23);

        $f = fn ($mat, $prenom, $nom, $sexe, $structure) => Facilitateur::create(['matricule' => $mat, 'prenom' => $prenom, 'nom' => $nom, 'sexe' => $sexe, 'date_naissance' => '1994-05-12', 'telephone' => '77 111 22 33', 'niveau_etude' => 'Licence', 'date_recrutement' => '2025-10-01', 'type_contrat' => 'Prestation', 'structure_id' => $structure->id, 'statut' => 'actif']);
        $fa1 = $f('FAC-001', 'Mariama', 'Sarr', 'F', $pk1);
        $fa2 = $f('FAC-002', 'Ibrahima', 'Diouf', 'M', $pk1);
        $fa3 = $f('FAC-003', 'Khady', 'Faye', 'F', $mb1);

        $c = function (Structure $st, $nom, $niveau, $fac) use ($codes) {
            $classe = new Classe(['nom_classe' => $nom, 'structure_id' => $st->id, 'facilitateur_id' => $fac?->id, 'niveau' => $niveau, 'langue' => 'Wolof', 'date_ouverture' => '2026-10-05']);
            $classe->code_classe = $codes->genererCodeClasse($st);
            $classe->save();

            return $classe;
        };
        $cl1 = $c($pk1, 'Étape 1 — groupe A', 'CI', $fa1);
        $c($pk1, 'Étape 2 — groupe B', 'CE1', $fa2);
        $c($pk2, 'Étape 1', 'CI', null);
        $c($mb1, 'Étape 1', 'CP', $fa3);

        $age = fn (int $ans, int $mois = 3) => Carbon::today()->subYears($ans)->subMonths($mois)->format('Y-m-d');
        $enfants = [
            // structure, prénom, nom, sexe, âge, situation, handicap, situation familiale, classe
            [$pk1, 'Fatou', 'Diop', 'F', 11, 'Descolarise', 'non', 'Orphelin', $cl1],
            [$pk1, 'Ousmane', 'Ba', 'M', 13, 'Non_scolarise', 'oui', 'Vit avec ses deux parents', $cl1],
            [$pk1, 'Aminata', 'Cissé', 'F', 12, 'Descolarise', 'non', 'Sous tutorat', $cl1],
            [$pk1, 'Mamadou', 'Gueye', 'M', 9, 'Non_scolarise', 'non', 'Parents divorcés', null],
            [$pk1, 'Ndeye', 'Sow', 'F', 10, 'Descolarise', 'non', 'Vit avec ses deux parents', $cl1],
            [$pk1, 'Cheikh', 'Mbaye', 'M', 12, 'Descolarise', 'non', 'Vit avec ses deux parents', null],
            [$pk1, 'Pape', 'Sy', 'M', 14, 'Non_scolarise', 'non', 'Vit avec ses deux parents', null],
            [$pk1, 'Modou', 'Kane', 'M', 8, 'Descolarise', 'non', 'Vit avec ses deux parents', null],
            [$pk1, 'Babacar', 'Thiam', 'M', 16, 'Descolarise', 'non', 'Vit avec ses deux parents', null],
            [$pk1, 'Awa', 'Ndao', 'F', 7, 'Non_scolarise', 'non', 'Vit avec ses deux parents', null],
            [$pk2, 'Seynabou', 'Diallo', 'F', 10, 'Non_scolarise', 'non', 'Orphelin', null],
            [$mb1, 'Lamine', 'Faye', 'M', 11, 'Descolarise', 'non', 'Vit avec ses deux parents', null],
            [$sl, 'Coumba', 'Seck', 'F', 9, 'Non_scolarise', 'non', 'Vit avec ses deux parents', null],
        ];
        foreach ($enfants as [$st, $prenom, $nom, $sexe, $ans, $situation, $handicap, $famille, $classe]) {
            $a = Apprenant::create(['prenom' => $prenom, 'nom' => $nom, 'sexe' => $sexe, 'date_naissance' => $age($ans), 'lieu_naissance' => $st->commune, 'situation' => $situation, 'derniere_classe' => $situation === 'Descolarise' ? 'CE1' : null, 'region' => $st->region, 'commune' => $st->commune, 'nom_parent' => 'Parent '.$nom, 'telephone_parent' => '77 222 33 44', 'handicap' => $handicap, 'situation_familiale' => $famille, 'structure_id' => $st->id, 'classe_id' => $classe?->id]);
            if ($prenom === 'Fatou') {
                Evaluation::create(['apprenant_id' => $a->id, 'classe_id' => $classe->id, 'libelle' => 'Évaluation du 1er mois', 'note1' => 9, 'note2' => 11, 'composition' => 10.5, 'moyenne' => Evaluation::calculerMoyenne(9, 11, 10.5)]);
                Evaluation::create(['apprenant_id' => $a->id, 'classe_id' => $classe->id, 'libelle' => 'Évaluation du 2e mois', 'note1' => 12, 'note2' => 13.5, 'composition' => 14, 'moyenne' => Evaluation::calculerMoyenne(12, 13.5, 14)]);
            } elseif ($classe) {
                Evaluation::create(['apprenant_id' => $a->id, 'classe_id' => $classe->id, 'libelle' => 'Évaluation du 1er mois', 'note1' => 11, 'note2' => 12, 'composition' => 13, 'moyenne' => Evaluation::calculerMoyenne(11, 12, 13)]);
            }
        }

        $u = fn ($email, $prenom, $nom, $role, $niveau, $ia = null, $ief = null, $structure = null) => User::create([
            'email' => $email, 'prenom' => $prenom, 'nom' => $nom, 'password' => self::MOT_DE_PASSE, 'role_id' => $role, 'status' => true,
            'niveau_acces' => $niveau, 'ia' => $ia, 'ief' => $ief, 'structure_id' => $structure,
        ]);
        $u('admin.demo@classes-passerelles.test', 'Awa', 'Sarr', 1, 'NATIONAL');
        $u('agent.pikine@classes-passerelles.test', 'Ibrahima', 'Faye', 2, 'IEF', 'IA Pikine Guediawaye', 'IEF Pikine');
        $u('agent.mbour@classes-passerelles.test', 'Mame', 'Diouf', 2, 'IEF', 'IA THIES', 'IEF Mbour 1');
        $u('agent.structure@classes-passerelles.test', 'Rokhaya', 'Ndiaye', 3, 'STRUCTURE', null, null, $pk1->id);
    }
}

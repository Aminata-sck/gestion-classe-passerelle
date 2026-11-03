<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Données de référence : menus, actions, profils et droits, référentiels et
 * découpage administratif. N'écrase pas une ligne existante (même identifiant).
 */
class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->inserer('permissions', [
            ['id' => 1, 'nom_permission' => 'Consulter', 'code' => 'consulter'],
            ['id' => 2, 'nom_permission' => 'Ajouter', 'code' => 'ajouter'],
            ['id' => 3, 'nom_permission' => 'Modifier', 'code' => 'modifier'],
            ['id' => 4, 'nom_permission' => 'Supprimer', 'code' => 'supprimer'],
        ]);

        $this->inserer('menus', [
            ['id' => 1, 'nom_menu' => 'Tableau de bord', 'icon' => 'tableau', 'ordre' => 1, 'url' => '/dashboard'],
            ['id' => 8, 'nom_menu' => 'Structures', 'icon' => 'structure', 'ordre' => 2, 'url' => '/structures'],
            ['id' => 7, 'nom_menu' => 'Classes', 'icon' => 'classe', 'ordre' => 3, 'url' => '/classes'],
            ['id' => 5, 'nom_menu' => 'Apprenants', 'icon' => 'apprenant', 'ordre' => 4, 'url' => '/apprenant'],
            ['id' => 2, 'nom_menu' => 'Facilitateurs', 'icon' => 'facilitateur', 'ordre' => 5, 'url' => '/facilitateur'],
            ['id' => 4, 'nom_menu' => 'Opérateurs', 'icon' => 'operateur', 'ordre' => 6, 'url' => '/operateur'],
            ['id' => 3, 'nom_menu' => 'Superviseurs', 'icon' => 'superviseur', 'ordre' => 7, 'url' => '/superviseur'],
            ['id' => 12, 'nom_menu' => 'Configuration', 'icon' => 'configuration', 'ordre' => 9, 'url' => null],
            ['id' => 6, 'nom_menu' => 'Administration', 'icon' => 'administration', 'ordre' => 10, 'url' => null],
        ]);

        $this->inserer('sous_menus', [
            ['id' => 1, 'menu_id' => 1, 'nom_sous_menu' => 'Tableau de bord', 'url' => '/dashboard', 'ordre' => 1],
            ['id' => 15, 'menu_id' => 8, 'nom_sous_menu' => 'Liste des structures', 'url' => '/structures', 'ordre' => 1],
            ['id' => 14, 'menu_id' => 7, 'nom_sous_menu' => 'Classes', 'url' => '/classes', 'ordre' => 1],
            ['id' => 13, 'menu_id' => 5, 'nom_sous_menu' => 'Apprenants', 'url' => '/apprenant', 'ordre' => 1],
            ['id' => 40, 'menu_id' => 5, 'nom_sous_menu' => 'Classement de sélection', 'url' => '/selection', 'ordre' => 2],
            ['id' => 2, 'menu_id' => 2, 'nom_sous_menu' => 'Facilitateurs', 'url' => '/facilitateur', 'ordre' => 1],
            ['id' => 4, 'menu_id' => 4, 'nom_sous_menu' => 'Opérateurs', 'url' => '/operateur', 'ordre' => 1],
            ['id' => 3, 'menu_id' => 3, 'nom_sous_menu' => 'Superviseurs', 'url' => '/superviseur', 'ordre' => 1],
            ['id' => 28, 'menu_id' => 12, 'nom_sous_menu' => "Types d'offre", 'url' => '/configuration/types-offre', 'ordre' => 1],
            ['id' => 29, 'menu_id' => 12, 'nom_sous_menu' => 'Langues nationales', 'url' => '/configuration/langues', 'ordre' => 2],
            ['id' => 30, 'menu_id' => 12, 'nom_sous_menu' => 'Sites abritants', 'url' => '/configuration/sites', 'ordre' => 3],
            ['id' => 31, 'menu_id' => 12, 'nom_sous_menu' => "Statuts d'occupation", 'url' => '/configuration/statuts', 'ordre' => 4],
            ['id' => 32, 'menu_id' => 12, 'nom_sous_menu' => 'Sources de financement', 'url' => '/configuration/financements', 'ordre' => 5],
            ['id' => 33, 'menu_id' => 12, 'nom_sous_menu' => 'Programmes', 'url' => '/configuration/programmes', 'ordre' => 6],
            ['id' => 34, 'menu_id' => 12, 'nom_sous_menu' => 'États des structures', 'url' => '/configuration/etats', 'ordre' => 7],
            ['id' => 35, 'menu_id' => 12, 'nom_sous_menu' => 'Nomenclature', 'url' => '/configuration/nomenclature', 'ordre' => 8],
            ['id' => 36, 'menu_id' => 12, 'nom_sous_menu' => 'Règles de codification', 'url' => '/configuration/codification', 'ordre' => 9],
            ['id' => 11, 'menu_id' => 6, 'nom_sous_menu' => 'Utilisateurs', 'url' => '/users', 'ordre' => 1],
            ['id' => 12, 'menu_id' => 6, 'nom_sous_menu' => 'Profils et droits', 'url' => '/profils', 'ordre' => 2],
            ['id' => 17, 'menu_id' => 6, 'nom_sous_menu' => 'Menus', 'url' => '/menus', 'ordre' => 3],
            ['id' => 41, 'menu_id' => 6, 'nom_sous_menu' => "Journal d'audit", 'url' => '/journal', 'ordre' => 4],
        ]);

        // Sur une base existante, les profils déjà créés gardent leurs droits :
        // seuls l'administrateur et les profils créés ici reçoivent les droits par défaut.
        $rolesExistants = DB::table('roles')->pluck('id')->all();
        $this->inserer('roles', [
            ['id' => 1, 'nom_role' => 'Administrateur'],
            ['id' => 2, 'nom_role' => 'Agent territorial'],
            ['id' => 3, 'nom_role' => 'Agent de structure'],
        ]);

        $tous = DB::table('sous_menus')->get(['id', 'menu_id']);
        $droits = [];
        foreach ($tous as $sm) {
            foreach ([1, 2, 3, 4] as $a) {
                $droits[] = ['role_id' => 1, 'menu_id' => $sm->menu_id, 'sous_menu_id' => $sm->id, 'permission_id' => $a];
            }
        }
        $agent = ['1:1' => [1], '8:15' => [1, 2, 3, 4], '7:14' => [1, 2, 3, 4], '5:13' => [1, 2, 3, 4], '5:40' => [1], '2:2' => [1, 2, 3, 4], '4:4' => [1], '3:3' => [1]];
        $structure = ['1:1' => [1], '7:14' => [1], '5:13' => [1, 2, 3], '2:2' => [1]];
        foreach ([2 => $agent, 3 => $structure] as $role => $carte) {
            if (in_array($role, $rolesExistants)) {
                continue;
            }
            foreach ($carte as $cle => $actions) {
                [$menu, $sm] = explode(':', $cle);
                foreach ($actions as $a) {
                    $droits[] = ['role_id' => $role, 'menu_id' => (int) $menu, 'sous_menu_id' => (int) $sm, 'permission_id' => $a];
                }
            }
        }
        DB::table('role_permissions')->insertOrIgnore($droits);

        $this->inserer('types_offre', [
            ['id' => 1, 'libelle' => "Classe d'alphabétisation fonctionnelle (CAF)", 'code' => '4660109381', 'prefixe' => 'CAF'],
            ['id' => 23, 'libelle' => 'École communautaire de base (ECB)', 'code' => '4294967292', 'prefixe' => 'ECB'],
            ['id' => 2, 'libelle' => 'Classe passerelle', 'code' => '4811771783', 'prefixe' => 'CLASSE-PASSERELLE'],
            ['id' => 4, 'libelle' => 'Daara communautaire', 'code' => '4122024924', 'prefixe' => 'DAARA-COM'],
        ]);
        $this->inserer('etat_structures', [
            ['id' => 1, 'libelle' => 'En attente de validation', 'couleur' => 'warning'],
            ['id' => 2, 'libelle' => 'Validée', 'couleur' => 'success'],
            ['id' => 3, 'libelle' => 'Ouverte', 'couleur' => 'primary'],
            ['id' => 4, 'libelle' => 'Fermée', 'couleur' => 'danger'],
            ['id' => 5, 'libelle' => 'Gelée', 'couleur' => 'secondary'],
        ]);
        $this->libelles('programmes', 'nom', ['PNEBJA', 'PALAM', 'Classes passerelles', 'UNICEF', 'USAID', 'PAPSE']);
        $this->libelles('sites_structure', 'libelle', ['École', 'Foyer', 'Mosquée', 'Abri']);
        $this->libelles('statuts_occupation', 'libelle', ['Propriétaire', 'Prêt', 'Locataire', 'Conventionné']);
        $this->libelles('sources_financement', 'libelle', ['État', 'ONG', 'Projet', 'OSC', 'PTF', 'Collectivité territoriale', 'Association', 'GIE']);

        if (DB::table('langues_nationales')->count() === 0) {
            $langues = json_decode(file_get_contents(__DIR__.'/data/langues.json'), true);
            DB::table('langues_nationales')->insert(array_map(fn ($l) => $l + ['actif' => 1], $langues));
        }
        if (DB::table('atlas')->count() === 0) {
            foreach (array_chunk(json_decode(file_get_contents(__DIR__.'/data/atlas.json'), true), 200) as $lot) {
                DB::table('atlas')->insert($lot);
            }
        }
    }

    /**
     * Ajoute les lignes absentes. Pour une ligne déjà présente, ne complète que
     * les colonnes vides (NULL) : aucune valeur saisie n'est écrasée.
     */
    private function inserer(string $table, array $lignes): void
    {
        DB::table($table)->insertOrIgnore($lignes);
        foreach ($lignes as $ligne) {
            foreach ($ligne as $colonne => $valeur) {
                if ($colonne !== 'id' && $valeur !== null) {
                    DB::table($table)->where('id', $ligne['id'])->whereNull($colonne)->update([$colonne => $valeur]);
                }
            }
        }
    }

    private function libelles(string $table, string $colonne, array $valeurs): void
    {
        if (DB::table($table)->count() === 0) {
            DB::table($table)->insert(array_map(fn ($v) => [$colonne => $v, 'actif' => 1], $valeurs));
        }
    }
}

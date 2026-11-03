<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Crée une table si elle n'existe pas, ou lui ajoute les colonnes manquantes.
 *
 * Les migrations de la plateforme doivent fonctionner sur une base neuve comme
 * sur la base existante de l'application CodeIgniter (mêmes noms de tables),
 * même lorsque celle-ci a été modifiée à la main : aucune donnée n'est
 * supprimée, seules les colonnes absentes sont ajoutées.
 */
class SchemaSync
{
    /**
     * @param  Closure(Blueprint): void  $define  définition complète de la table
     * @param  array<string, Closure(Blueprint): void>  $columns  ajouts particuliers (prioritaires)
     */
    public static function table(string $table, Closure $define, array $columns = []): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $define);

            return;
        }

        foreach ($columns as $column => $add) {
            if (! Schema::hasColumn($table, $column)) {
                Schema::table($table, $add);
            }
        }

        // Toutes les autres colonnes de la définition, déduites automatiquement.
        $existantes = array_map('strtolower', Schema::getColumnListing($table));
        $plan = new Blueprint(DB::connection(), $table, $define);

        foreach ($plan->getAddedColumns() as $colonne) {
            $nom = $colonne->get('name');
            if (in_array(strtolower($nom), $existantes, true) || $colonne->get('autoIncrement')) {
                continue;
            }
            $attributs = collect($colonne->getAttributes())
                ->except(['type', 'name', 'autoIncrement', 'primary', 'unique', 'index', 'fulltext', 'spatialIndex', 'after', 'first', 'change'])
                ->all();
            // Les lignes déjà présentes n'ont pas de valeur : colonne facultative si aucun défaut.
            if (! array_key_exists('default', $attributs) && empty($attributs['useCurrent'])) {
                $attributs['nullable'] = true;
            }
            Schema::table($table, function (Blueprint $t) use ($colonne, $nom, $attributs) {
                $t->addColumn($colonne->get('type'), $nom, $attributs);
            });
            $existantes[] = strtolower($nom);
        }
    }
}

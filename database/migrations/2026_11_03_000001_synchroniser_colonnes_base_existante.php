<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Rejoue les trois migrations de création : sur une base reprise dont
 * certaines tables avaient été créées à la main, ajoute les colonnes
 * qui manquaient encore. Sans effet sur une base déjà complète.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            '2026_11_01_000001_create_acces_tables.php',
            '2026_11_01_000002_create_referentiels_tables.php',
            '2026_11_01_000003_create_pedagogie_tables.php',
        ] as $fichier) {
            (require database_path('migrations/'.$fichier))->up();
        }
    }

    public function down(): void
    {
        // Rien à défaire : seules des colonnes manquantes ont été ajoutées.
    }
};

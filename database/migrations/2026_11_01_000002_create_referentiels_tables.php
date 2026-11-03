<?php

use App\Support\SchemaSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Référentiels paramétrables : types d'offre, états, langues, sites, statuts,
 * sources de financement, programmes, nomenclature et règles de codification.
 */
return new class extends Migration
{
    public function up(): void
    {
        SchemaSync::table('types_offre', function (Blueprint $t) {
            $t->id();
            $t->string('libelle', 100);
            $t->string('code', 50);       // le dernier chiffre est le suffixe du code de structure
            $t->string('prefixe', 50);    // préfixe du code de classe
            $t->text('description')->nullable();
            $t->boolean('actif')->default(true);
            $t->timestamps();
        });

        SchemaSync::table('etat_structures', function (Blueprint $t) {
            $t->id();
            $t->string('libelle', 100);
            $t->string('couleur', 20)->nullable();
            $t->text('description')->nullable();
            $t->boolean('actif')->default(true);
            $t->timestamps();
        });

        SchemaSync::table('langues_nationales', function (Blueprint $t) {
            $t->id();
            $t->string('nom', 100);
            $t->string('code', 20)->nullable();
            $t->boolean('actif')->default(true);
            $t->timestamps();
        });

        foreach (['programmes' => 'nom', 'sites_structure' => 'libelle', 'sources_financement' => 'libelle', 'statuts_occupation' => 'libelle'] as $table => $label) {
            SchemaSync::table($table, function (Blueprint $t) use ($label) {
                $t->id();
                $t->string($label, 150);
                $t->text('description')->nullable();
                $t->boolean('actif')->default(true);
                $t->timestamps();
            });
        }

        foreach (['nomenclatures', 'regles_codification'] as $table) {
            SchemaSync::table($table, function (Blueprint $t) use ($table) {
                $t->id();
                $t->unsignedBigInteger('type_offre_id')->nullable();
                if ($table === 'regles_codification') {
                    $t->unsignedInteger('numero_ordre')->nullable();
                }
                $t->string('code', 50);
                $t->string('libelle', 150);
                $t->string('nom_site')->nullable();
                $t->string('prefixe', 50)->nullable();
                $t->string('format', 100)->nullable();
                $t->text('description')->nullable();
                $t->boolean('actif')->default(true);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
    }
};

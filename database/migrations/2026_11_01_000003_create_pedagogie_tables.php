<?php

use App\Support\SchemaSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Domaine pédagogique : opérateurs, superviseurs, structures, facilitateurs,
 * classes, apprenants et évaluations.
 */
return new class extends Migration
{
    public function up(): void
    {
        SchemaSync::table('operateur', function (Blueprint $t) {
            $t->id();
            $t->string('code_operateur', 100);
            $t->string('nom_organisation', 150);
            $t->string('type_operateur', 100);
            $t->string('prenom_responsable', 100);
            $t->string('nom_responsable', 100);
            $t->string('telephone', 30);
            $t->string('email', 150)->nullable();
            $t->string('adresse')->nullable();
            $t->string('numero_agrement', 100)->nullable();
            $t->date('date_debut');
            $t->date('date_fin')->nullable();
            $t->unsignedBigInteger('structure_id')->nullable();
            $t->string('statut', 20)->default('actif');
            $t->timestamps();
        });

        SchemaSync::table('superviseur', function (Blueprint $t) {
            $t->id();
            $t->string('matricule', 50);
            $t->string('prenom', 100);
            $t->string('nom', 100);
            $t->enum('sexe', ['M', 'F']);
            $t->string('telephone', 20)->nullable();
            $t->string('email', 150)->nullable();
            $t->enum('fonction', ['IEF', 'IA', 'Central']);
            $t->string('structure_affectation', 150)->nullable();
            $t->string('region', 100)->nullable();
            $t->string('departement', 100)->nullable();
            $t->date('date_affectation')->nullable();
            $t->enum('statut', ['actif', 'inactif'])->default('actif');
            $t->timestamps();
        });

        SchemaSync::table('structures', function (Blueprint $t) {
            $t->id();
            $t->string('code_structure', 10)->unique();
            $t->string('nom_structure');
            $t->unsignedBigInteger('type_offre_id')->nullable()->index();
            $t->string('region');
            $t->string('departement');
            $t->string('commune');
            $t->string('quartier');
            $t->string('ia')->index();
            $t->string('ief')->index();
            $t->decimal('latitude', 10, 8)->nullable();
            $t->decimal('longitude', 11, 8)->nullable();
            $t->string('langue_nationale');
            $t->unsignedBigInteger('operateur_id')->nullable()->index();
            $t->enum('etat', ['EN_ATTENTE', 'VALIDE', 'OUVERT', 'GELE', 'FERME'])->default('EN_ATTENTE');
            $t->unsignedBigInteger('ancienne_structure_id')->nullable()->index();
            $t->timestamps();
            $t->softDeletes();
        }, [
            'type_offre_id' => fn (Blueprint $t) => $t->unsignedBigInteger('type_offre_id')->nullable(),
            'ancienne_structure_id' => fn (Blueprint $t) => $t->unsignedBigInteger('ancienne_structure_id')->nullable(),
            'deleted_at' => fn (Blueprint $t) => $t->softDeletes(),
        ]);

        SchemaSync::table('facilitateur', function (Blueprint $t) {
            $t->id();
            $t->string('matricule', 100);
            $t->string('prenom', 100);
            $t->string('nom', 100);
            $t->string('sexe', 20);
            $t->date('date_naissance');
            $t->string('telephone', 30);
            $t->string('email', 150)->nullable();
            $t->string('adresse')->nullable();
            $t->string('niveau_etude', 150)->nullable();
            $t->string('specialite', 150)->nullable();
            $t->date('date_recrutement');
            $t->string('type_contrat', 100);
            $t->unsignedBigInteger('structure_id')->nullable()->index();
            $t->unsignedBigInteger('classe_id')->nullable();
            $t->string('statut', 20)->default('actif');
            $t->timestamps();
        });

        SchemaSync::table('classes', function (Blueprint $t) {
            $t->id();
            $t->string('nom_classe', 100);
            $t->string('code_classe', 50)->unique();
            $t->unsignedBigInteger('structure_id')->nullable()->index();
            $t->unsignedBigInteger('facilitateur_id')->nullable()->index();
            $t->enum('niveau', ['CI', 'CP', 'CE1', 'CE2']);
            $t->string('langue', 50)->nullable();
            $t->date('date_ouverture')->nullable();
            $t->timestamps();
        });

        SchemaSync::table('apprenants', function (Blueprint $t) {
            $t->id();
            $t->string('nom', 100);
            $t->string('prenom', 100);
            $t->enum('sexe', ['M', 'F']);
            $t->date('date_naissance')->nullable();
            $t->string('lieu_naissance', 150)->nullable();
            $t->enum('situation', ['Descolarise', 'Non_scolarise'])->nullable();
            $t->string('derniere_classe', 100)->nullable();
            $t->text('cause_descolarisation')->nullable();
            $t->string('region', 100)->nullable();
            $t->string('departement', 100)->nullable();
            $t->string('commune', 100)->nullable();
            $t->string('nom_parent', 150)->nullable();
            $t->string('telephone_parent', 20)->nullable();
            $t->enum('handicap', ['oui', 'non'])->default('non');
            $t->string('situation_familiale', 150)->nullable();
            $t->unsignedBigInteger('structure_id')->nullable()->index();
            $t->unsignedBigInteger('classe_id')->nullable()->index();
            $t->timestamps();
        }, [
            'structure_id' => fn (Blueprint $t) => $t->unsignedBigInteger('structure_id')->nullable(),
            'classe_id' => fn (Blueprint $t) => $t->unsignedBigInteger('classe_id')->nullable(),
        ]);

        SchemaSync::table('evaluations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('apprenant_id')->index();
            $t->unsignedBigInteger('classe_id')->nullable();
            $t->string('libelle', 100);
            $t->decimal('note1', 4, 2);
            $t->decimal('note2', 4, 2);
            $t->decimal('composition', 4, 2);
            $t->decimal('moyenne', 4, 2);
            $t->unsignedBigInteger('saisi_par')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
    }
};

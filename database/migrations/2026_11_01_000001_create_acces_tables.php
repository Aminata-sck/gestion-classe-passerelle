<?php

use App\Support\SchemaSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes, profils, menus et droits par profil (menu x sous-menu x action).
 */
return new class extends Migration
{
    public function up(): void
    {
        SchemaSync::table('roles', function (Blueprint $t) {
            $t->id();
            $t->string('nom_role', 100)->unique();
            $t->timestamps();
        });

        SchemaSync::table('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('nom_permission', 100);
            $t->string('code', 100)->default('');
            $t->string('description')->nullable();
            $t->boolean('statut')->default(true);
        });

        SchemaSync::table('menus', function (Blueprint $t) {
            $t->id();
            $t->string('nom_menu');
            $t->string('icon')->nullable();
            $t->integer('ordre')->default(0);
            $t->boolean('statut')->default(true);
            $t->boolean('visible')->default(true);
            $t->string('url')->nullable();
        });

        SchemaSync::table('sous_menus', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('menu_id')->index();
            $t->string('nom_sous_menu', 100);
            $t->string('url', 150)->nullable();
            $t->string('icon', 50)->nullable();
            $t->integer('ordre')->default(0);
            $t->unsignedBigInteger('permission_id')->default(1);
            $t->boolean('statut')->default(true);
            $t->timestamp('created_at')->nullable()->useCurrent();
        });

        SchemaSync::table('role_permissions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('role_id')->index();
            $t->unsignedBigInteger('menu_id');
            $t->unsignedBigInteger('sous_menu_id');
            $t->unsignedBigInteger('permission_id');
            $t->unique(['role_id', 'menu_id', 'sous_menu_id', 'permission_id'], 'role_permissions_unique');
        });

        // Découpage administratif (régions, IA, IEF...) utilisé pour le rattachement.
        SchemaSync::table('atlas', function (Blueprint $t) {
            $t->id();
            $t->integer('id_atlas')->index();
            $t->string('libelle_structure');
            $t->integer('id_parent_atlas')->nullable()->index();
            $t->integer('id_hierarchie')->nullable()->index();
            $t->string('libelle_hierarchie')->nullable();
            $t->timestamps();
        });

        SchemaSync::table('users', function (Blueprint $t) {
            $t->id();
            $t->string('nom', 100);
            $t->string('prenom', 100)->nullable();
            $t->string('ine', 50)->nullable();
            $t->string('email', 150)->unique();
            $t->string('password');
            $t->unsignedBigInteger('role_id')->index();
            $t->boolean('status')->default(true);
            $t->boolean('must_change_password')->default(false);
            $t->enum('niveau_acces', ['NATIONAL', 'IA', 'IEF', 'STRUCTURE'])->default('NATIONAL');
            $t->string('ia')->nullable();
            $t->string('ief')->nullable();
            $t->unsignedBigInteger('structure_id')->nullable()->index();
            $t->rememberToken();
            $t->timestamps();
        }, [
            'niveau_acces' => fn (Blueprint $t) => $t->enum('niveau_acces', ['NATIONAL', 'IA', 'IEF', 'STRUCTURE'])->default('NATIONAL'),
            'ia' => fn (Blueprint $t) => $t->string('ia')->nullable(),
            'ief' => fn (Blueprint $t) => $t->string('ief')->nullable(),
            'structure_id' => fn (Blueprint $t) => $t->unsignedBigInteger('structure_id')->nullable(),
            'remember_token' => fn (Blueprint $t) => $t->rememberToken(),
            'must_change_password' => fn (Blueprint $t) => $t->boolean('must_change_password')->default(false),
        ]);

        // Annuaire du personnel du MEN (recherche par IEN). Aucune donnée n'est fournie.
        SchemaSync::table('personnels', function (Blueprint $t) {
            $t->id();
            $t->string('prenom')->nullable();
            $t->string('nom')->nullable();
            $t->string('addMail')->nullable()->index();
            $t->string('id_atlas')->nullable()->index();
            $t->timestamps();
        });

        SchemaSync::table('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('user_email', 150)->nullable();
            $t->string('action', 50)->index();
            $t->string('entity', 100)->nullable();
            $t->string('entity_id', 50)->nullable();
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent')->nullable();
            $t->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        // Volontairement vide : ces tables peuvent contenir des données réelles.
    }
};

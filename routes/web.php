<?php

use App\Http\Controllers\ApprenantController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\FacilitateurController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OperateurController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\ReferentielController;
use App\Http\Controllers\SelectionController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\SuperviseurController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
| Chaque route protégée porte le droit exigé sous la forme
| permission:menu,sous-menu,action (1 consulter, 2 ajouter, 3 modifier, 4 supprimer).
| Les identifiants de menus sont ceux de la base existante.
*/

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'compte'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/password', [AuthController::class, 'editPassword'])->name('password.edit');
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');

    Route::get('/dashboard', DashboardController::class)->middleware('permission:1,1,1')->name('dashboard');

    // Structures
    Route::controller(StructureController::class)->prefix('structures')->name('structures.')->group(function () {
        Route::get('/', 'index')->middleware('permission:8,15,1')->name('index');
        Route::get('/create', 'create')->middleware('permission:8,15,2')->name('create');
        Route::post('/', 'store')->middleware('permission:8,15,2')->name('store');
        Route::get('/{id}', 'show')->whereNumber('id')->middleware('permission:8,15,1')->name('show');
        Route::get('/{id}/edit', 'edit')->whereNumber('id')->middleware('permission:8,15,3')->name('edit');
        Route::put('/{id}', 'update')->whereNumber('id')->middleware('permission:8,15,3')->name('update');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware('permission:8,15,4')->name('destroy');
    });

    // Classes
    Route::get('/classes', [ClasseController::class, 'index'])->middleware('permission:7,14,1')->name('classes.index');
    Route::post('/classes', [ClasseController::class, 'store'])->middleware('permission:7,14,2')->name('classes.store');
    Route::put('/classes/{classe}', [ClasseController::class, 'update'])->middleware('permission:7,14,3')->name('classes.update');
    Route::delete('/classes/{classe}', [ClasseController::class, 'destroy'])->middleware('permission:7,14,4')->name('classes.destroy');

    // Facilitateurs
    Route::get('/facilitateur', [FacilitateurController::class, 'index'])->middleware('permission:2,2,1')->name('facilitateurs.index');
    Route::post('/facilitateur', [FacilitateurController::class, 'store'])->middleware('permission:2,2,2')->name('facilitateurs.store');
    Route::put('/facilitateur/{facilitateur}', [FacilitateurController::class, 'update'])->middleware('permission:2,2,3')->name('facilitateurs.update');
    Route::delete('/facilitateur/{facilitateur}', [FacilitateurController::class, 'destroy'])->middleware('permission:2,2,4')->name('facilitateurs.destroy');

    // Apprenants, évaluations et sélection
    Route::get('/apprenant', [ApprenantController::class, 'index'])->middleware('permission:5,13,1')->name('apprenants.index');
    Route::post('/apprenant', [ApprenantController::class, 'store'])->middleware('permission:5,13,2')->name('apprenants.store');
    Route::get('/apprenant/{id}', [ApprenantController::class, 'show'])->whereNumber('id')->middleware('permission:5,13,1')->name('apprenants.show');
    Route::put('/apprenant/{apprenant}', [ApprenantController::class, 'update'])->middleware('permission:5,13,3')->name('apprenants.update');
    Route::delete('/apprenant/{apprenant}', [ApprenantController::class, 'destroy'])->middleware('permission:5,13,4')->name('apprenants.destroy');
    Route::post('/apprenant/{apprenant}/evaluations', [EvaluationController::class, 'store'])->middleware('permission:5,13,3')->name('evaluations.store');
    Route::delete('/apprenant/{apprenant}/evaluations/{evaluation}', [EvaluationController::class, 'destroy'])->middleware('permission:5,13,3')->name('evaluations.destroy');
    Route::get('/selection', SelectionController::class)->middleware('permission:5,40,1')->name('selection');

    // Opérateurs et superviseurs
    Route::get('/operateur', [OperateurController::class, 'index'])->middleware('permission:4,4,1')->name('operateurs.index');
    Route::post('/operateur', [OperateurController::class, 'store'])->middleware('permission:4,4,2')->name('operateurs.store');
    Route::put('/operateur/{operateur}', [OperateurController::class, 'update'])->middleware('permission:4,4,3')->name('operateurs.update');
    Route::delete('/operateur/{operateur}', [OperateurController::class, 'destroy'])->middleware('permission:4,4,4')->name('operateurs.destroy');
    Route::get('/superviseur', [SuperviseurController::class, 'index'])->middleware('permission:3,3,1')->name('superviseurs.index');
    Route::post('/superviseur', [SuperviseurController::class, 'store'])->middleware('permission:3,3,2')->name('superviseurs.store');
    Route::put('/superviseur/{superviseur}', [SuperviseurController::class, 'update'])->middleware('permission:3,3,3')->name('superviseurs.update');
    Route::delete('/superviseur/{superviseur}', [SuperviseurController::class, 'destroy'])->middleware('permission:3,3,4')->name('superviseurs.destroy');

    // Administration
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:6,11,1')->name('users.index');
    Route::get('/users/personnel', [UserController::class, 'rechercherPersonnel'])->middleware('permission:6,11,2')->name('users.personnel');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:6,11,2')->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:6,11,3')->name('users.update');
    Route::post('/users/{user}/blocage', [UserController::class, 'toggle'])->middleware('permission:6,11,3')->name('users.toggle');
    Route::post('/users/{user}/reinitialisation', [UserController::class, 'reset'])->middleware('permission:6,11,3')->name('users.reset');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:6,11,4')->name('users.destroy');

    Route::get('/profils', [ProfilController::class, 'index'])->middleware('permission:6,12,1')->name('profils.index');
    Route::post('/profils', [ProfilController::class, 'store'])->middleware('permission:6,12,2')->name('profils.store');
    Route::put('/profils/{profil}', [ProfilController::class, 'update'])->middleware('permission:6,12,3')->name('profils.update');
    Route::delete('/profils/{profil}', [ProfilController::class, 'destroy'])->middleware('permission:6,12,4')->name('profils.destroy');

    Route::get('/menus', [MenuController::class, 'index'])->middleware('permission:6,17,1')->name('menus.index');
    Route::post('/menus', [MenuController::class, 'storeMenu'])->middleware('permission:6,17,2')->name('menus.store');
    Route::put('/menus/{menu}', [MenuController::class, 'updateMenu'])->middleware('permission:6,17,3')->name('menus.update');
    Route::delete('/menus/{menu}', [MenuController::class, 'destroyMenu'])->middleware('permission:6,17,4')->name('menus.destroy');
    Route::post('/sous-menus', [MenuController::class, 'storeSousMenu'])->middleware('permission:6,17,2')->name('sous-menus.store');
    Route::put('/sous-menus/{sousMenu}', [MenuController::class, 'updateSousMenu'])->middleware('permission:6,17,3')->name('sous-menus.update');
    Route::delete('/sous-menus/{sousMenu}', [MenuController::class, 'destroySousMenu'])->middleware('permission:6,17,4')->name('sous-menus.destroy');

    // Anciennes adresses (application CodeIgniter) : redirection vers les nouvelles.
    foreach (\App\Services\PermissionService::ANCIENNES_URLS as $ancienne => $nouvelle) {
        Route::redirect($ancienne, $nouvelle, 301);
    }
    Route::get('/journal', AuditController::class)->middleware('permission:6,41,1')->name('journal');

    // Référentiels : un sous-menu (et donc un jeu de droits) par référentiel
    foreach (ReferentielController::REFERENTIELS as $cle => $def) {
        $d = ReferentielController::MENU.','.$def['sous_menu'];
        Route::get("/configuration/{$cle}", [ReferentielController::class, 'index'])->defaults('referentiel', $cle)->middleware("permission:{$d},1")->name("referentiels.{$cle}");
        Route::post("/configuration/{$cle}", [ReferentielController::class, 'store'])->defaults('referentiel', $cle)->middleware("permission:{$d},2");
        Route::put("/configuration/{$cle}/{id}", [ReferentielController::class, 'update'])->defaults('referentiel', $cle)->middleware("permission:{$d},3");
        Route::delete("/configuration/{$cle}/{id}", [ReferentielController::class, 'destroy'])->defaults('referentiel', $cle)->middleware("permission:{$d},4");
    }
});

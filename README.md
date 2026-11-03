# Plateforme de gestion des classes passerelles — MEN / DALN

Application web de gestion des classes passerelles (structures EBJA, classes,
facilitateurs, apprenants, sélection, évaluations, tableaux de bord), réalisée
dans le cadre du mémoire de Master 2 de Mme Madjiguéne Aminata SECK
(Génie logiciel et administration réseaux).

| Couche | Technologie |
|---|---|
| Serveur | PHP 8.2 ou plus, **Laravel 12** |
| Liaison serveur / interface | **Inertia.js 2** |
| Interface | **Vue.js 3** (composants à fichier unique), construite avec Vite 7 |
| Base de données | MySQL 8 ou MariaDB 10.6+ |
| Tests | PHPUnit 11 (unitaires et fonctionnels), Playwright (recette de bout en bout) |

Le dossier `vendor/` (dépendances PHP) et `public/build/` (interface compilée)
sont fournis : **Composer et Node.js ne sont pas nécessaires** pour lancer l'application.

---

## 1. Installation sur un PC (XAMPP)

1. Installer **XAMPP avec PHP 8.2 ou plus**, puis démarrer **MySQL** dans le panneau XAMPP.
2. Décompresser ce dossier, par exemple dans `C:\xampp\htdocs\classes-passerelles`.
3. Ouvrir un terminal dans ce dossier, puis :

```bash
copy .env.example .env          # sous Linux / macOS : cp .env.example .env
php artisan key:generate
```

4. Ouvrir `.env` et renseigner la base de données :
   - `DB_DATABASE=classe_informelle` pour **reprendre la base existante** (ses données sont conservées) ;
   - ou un nom de **nouvelle base** (à créer d'abord dans phpMyAdmin, interclassement `utf8mb4_unicode_ci`).

5. Créer les tables et les données de référence :

```bash
php artisan migrate --force
php artisan db:seed --force
```

   Les migrations sont **idempotentes** : sur la base existante, elles n'ajoutent
   que les tables et colonnes manquantes (journal d'audit, évaluations, périmètre
   des comptes…) sans rien supprimer. Le chargement des références n'écrase aucune
   ligne existante et ne modifie pas les droits des profils déjà définis.

6. Créer le premier administrateur (si aucun compte n'existe) :

```bash
php artisan compte:admin admin@education.sn "Nom de l'administrateur"
```

   Le mot de passe temporaire s'affiche ; il devra être changé à la première connexion.

7. Lancer l'application :

```bash
php artisan serve
```

   puis ouvrir <http://localhost:8000>.

### Données de démonstration (facultatif)

Pour les captures d'écran et la démonstration, **sur une base vide dédiée** :

```bash
php artisan migrate:fresh --seed --force
php artisan db:seed --class=DemoSeeder --force
```

Comptes créés (mot de passe `Demo#2026passe`) — **données entièrement fictives** :

| Compte | Profil | Périmètre |
|---|---|---|
| admin.demo@classes-passerelles.test | Administrateur | National |
| agent.pikine@classes-passerelles.test | Agent territorial | IEF Pikine |
| agent.mbour@classes-passerelles.test | Agent territorial | IEF Mbour 1 |
| agent.structure@classes-passerelles.test | Agent de structure | Classe passerelle de Thiaroye |

> `migrate:fresh` **efface toute la base** : ne jamais l'utiliser sur la base réelle.

---

## 2. Commandes d'exploitation

| Commande | Rôle |
|---|---|
| `php artisan motdepasse email@exemple.sn` | Réinitialise un mot de passe (temporaire, à changer à la connexion) |
| `php artisan compte:admin email "Nom"` | Crée un administrateur de niveau national |
| `php artisan route:list` | Liste les 96 routes et les droits exigés |
| `php artisan optimize` | Met en cache configuration et routes (production) |

## 3. Tests

```bash
php vendor/bin/phpunit --testdox          # 36 tests, base SQLite en mémoire
```

Recette de bout en bout (24 contrôles F01–F08 et N01–N14, temps de réponse),
sur la base de démonstration, avec Python et Playwright installés :

```bash
set APP_ENV=production&& set APP_DEBUG=false&& php artisan serve --port=8001
python tests/e2e/recette.py http://127.0.0.1:8001
```

## 4. Modifier l'interface Vue

Seulement si vous modifiez les fichiers de `resources/js` ou `resources/css` :

```bash
npm install
npm run build        # ou « npm run dev » pendant le développement
```

## 5. Organisation du code

```
app/
  Http/Controllers/     contrôleurs minces (un par écran)
  Http/Middleware/      CheckPermission (droits), EnsureAccountIsUsable, SecurityHeaders, HandleInertiaRequests
  Http/Requests/        validation des structures et des apprenants (périmètre vérifié avant)
  Models/               modèles Eloquent (+ trait Auditable : journal automatique)
  Services/             règles métier : AccessScope, StructureCodeService, SelectionService,
                        StructureStateMachine, PermissionService, AuditLogger, LoginThrottle
database/migrations/    schéma (compatible avec la base existante)
database/seeders/       références (menus, droits, découpage administratif) et démonstration
resources/js/Pages/     écrans Vue (Inertia)
resources/js/Components composants réutilisables (formulaires, fenêtres, pagination…)
routes/web.php          routes et droits requis (menu:sous-menu:action)
tests/                  Unit/, Feature/ (PHPUnit) et e2e/ (Playwright)
```

## 6. Sécurité de l'exploitation

- Ne jamais diffuser le fichier `.env` (clé de l'application, mot de passe de la base).
- Changer le mot de passe MySQL s'il a circulé ; ne pas utiliser `root` sans mot de passe en production.
- En production : `APP_ENV=production`, `APP_DEBUG=false`, HTTPS et `SESSION_SECURE_COOKIE=true`.
- Les sauvegardes de la base contiennent des données personnelles d'enfants : les chiffrer et en limiter l'accès.

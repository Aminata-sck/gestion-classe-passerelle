"""
Recette de bout en bout (navigateur réel) : scénarios fonctionnels F01-F08,
contrôles de non-régression N01-N14 et temps de réponse.

Prérequis : base de démonstration (php artisan migrate:fresh --seed puis
php artisan db:seed --class=DemoSeeder) et serveur lancé en mode production :
    APP_ENV=production APP_DEBUG=false php artisan serve --port=8001
Usage : python tests/e2e/recette.py [http://127.0.0.1:8001]
"""
import re, sys, statistics, time
from playwright.sync_api import sync_playwright

BASE = sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:8001'
MDP = 'Demo#2026passe'
ADMIN, PIKINE, MBOUR, STRUCT = ('admin.demo', 'agent.pikine', 'agent.mbour', 'agent.structure')
resultats = []


def compte(nom):
    return f'{nom}@classes-passerelles.test'


def verifier(ref, libelle, condition, detail=''):
    resultats.append((ref, libelle, bool(condition), detail))
    print(f"{'OK ' if condition else 'ÉCHEC'} {ref:4} {libelle}" + (f'  [{detail}]' if detail else ''))


def connexion(navigateur, nom, mdp=MDP):
    ctx = navigateur.new_context(viewport={'width': 1440, 'height': 900}, locale='fr-FR')
    page = ctx.new_page()
    page.goto(f'{BASE}/login')
    page.fill('#email', compte(nom))
    page.fill('#password', mdp)
    page.click('button[type=submit]')
    page.wait_for_load_state('networkidle')
    return ctx, page


def aller(page, url):
    r = page.goto(BASE + url)
    page.wait_for_load_state('networkidle')
    return r


def attendre(page):
    # Inertia navigue en AJAX : on laisse partir la requête puis on attend le réseau.
    page.wait_for_timeout(700)
    page.wait_for_load_state('networkidle')


def id_structure(page, nom):
    aller(page, '/structures?recherche=' + nom)
    lien = page.locator('table a[href^="/structures/"]').first
    return int(lien.get_attribute('href').rsplit('/', 1)[1])


with sync_playwright() as p:
    nav = p.chromium.launch()
    anonyme = nav.new_context().new_page()

    # ---------------- Non-régression ----------------
    r = anonyme.goto(BASE + '/structures')
    verifier('N01', 'Accès sans authentification redirigé vers la connexion', anonyme.url.endswith('/login'))

    r = anonyme.request.post(BASE + '/login', form={'email': compte(ADMIN), 'password': MDP}, max_redirects=0)
    apres = anonyme.request.get(BASE + '/dashboard', max_redirects=0)
    verifier('N02', 'Formulaire sans jeton CSRF rejeté', r.status in (302, 419) and apres.status == 302, f'POST {r.status}, puis /dashboard {apres.status}')

    h = anonyme.request.get(BASE + '/login').headers
    verifier('N03', 'En-têtes HTTP de protection présents',
             h.get('x-frame-options') == 'DENY' and h.get('x-content-type-options') == 'nosniff' and 'content-security-policy' in h)

    ctx_a, admin = connexion(nav, ADMIN)
    r = aller(admin, '/structures/999999')
    corps = admin.content()
    verifier('N04', 'Aucune trace technique affichée (mode production)', r.status == 404 and 'vendor/laravel' not in corps and 'Stack trace' not in corps, f'statut {r.status}')

    for _ in range(5):
        anonyme.goto(BASE + '/login'); anonyme.fill('#email', compte(STRUCT)); anonyme.fill('#password', 'faux-mot-de-passe'); anonyme.click('button[type=submit]'); anonyme.wait_for_load_state('networkidle')
    anonyme.goto(BASE + '/login'); anonyme.fill('#email', compte(STRUCT)); anonyme.fill('#password', MDP); anonyme.click('button[type=submit]'); anonyme.wait_for_load_state('networkidle')
    verifier('N05', 'Suspension après cinq mots de passe erronés', anonyme.url.endswith('/login') and 'Trop de tentatives' in anonyme.content())

    ok = True
    for nom in (ADMIN, PIKINE, MBOUR):
        c, pg = connexion(nav, nom)
        ok = ok and pg.url.endswith('/dashboard')
        c.close()
    verifier('N06', 'Connexion des comptes de démonstration', ok)

    ctx_m, mbour = connexion(nav, MBOUR)
    r = aller(mbour, '/users')
    verifier('N07', "Route d'administration refusée sans le droit requis", r.status == 403, f'statut {r.status}')

    id_thiaroye = id_structure(admin, 'Thiaroye')
    r = aller(mbour, f'/structures/{id_thiaroye}')
    verifier('N08', "Fiche d'une structure d'une autre IEF refusée", r.status == 404 and 'Introuvable' in mbour.content(), f'statut {r.status}')

    aller(mbour, '/structures')
    texte = mbour.locator('main').inner_text()
    verifier('N09', "Liste sans les structures d'une autre IEF", 'Thiaroye' not in texte and 'Guinaw' not in texte and 'Guet Ndar' not in texte)
    verifier('N09b', 'Structures de sa propre IEF visibles', 'Grand Mbour' in texte and 'Saly' in texte)

    r1 = admin.request.get(f'{BASE}/structures/delete/{id_thiaroye}', max_redirects=0)
    r2 = admin.request.get(f'{BASE}/structures/{id_thiaroye}/delete', max_redirects=0)
    toujours = aller(admin, f'/structures/{id_thiaroye}').status == 200
    verifier('N10', 'Suppression par simple lien refusée', r1.status == 404 and r2.status == 404 and toujours, f'{r1.status}/{r2.status}')

    aller(admin, f'/structures/{id_thiaroye}/edit')
    code_avant = admin.locator('input[readonly]').first.input_value()
    jeton = admin.evaluate("() => decodeURIComponent(document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')).split('=')[1])")
    # Envoi direct des valeurs actuelles de la fiche, avec un code forgé en plus.
    import json
    valeurs = json.loads(admin.locator('#app').get_attribute('data-page'))['props']['structure']
    champs = {k: '' if v is None else str(v) for k, v in valeurs.items() if k not in ('id', 'code')}
    admin.request.put(f'{BASE}/structures/{id_thiaroye}', max_redirects=0, headers={'X-XSRF-TOKEN': jeton, 'X-Requested-With': 'XMLHttpRequest'},
                      form=champs | {'code_structure': '4999999993'})
    aller(admin, f'/structures/{id_thiaroye}/edit')
    verifier('N11', 'Code de structure non modifiable par formulaire', admin.locator('input[readonly]').first.input_value() == code_avant, code_avant)

    cookie = next(c for c in ctx_a.cookies() if c['name'].endswith('-session'))
    verifier('N12', 'Cookie de session protégé (HttpOnly, SameSite)', cookie['httpOnly'] and cookie['sameSite'] in ('Lax', 'Strict'))

    aller(admin, '/journal')
    verifier('N13', 'Historique des actions disponible', admin.locator('tbody tr').count() > 0 and 'ACCES_REFUSE' in admin.content())

    alertes = []
    admin.on('dialog', lambda d: (alertes.append(d.message), d.dismiss()))
    aller(admin, '/facilitateur')
    admin.get_by_role('button', name=re.compile('Nouveau facilitateur')).click()
    for champ, valeur in {'matricule': 'E2E-001', 'prenom': '<script>alert(1)</script>', 'nom': '<img src=x onerror=alert(2)>',
                          'date_naissance': '1990-01-01', 'telephone': '770000000', 'type_contrat': 'Contractuel',
                          'date_recrutement': '2026-10-01'}.items():
        admin.fill(f'#f-{champ}', valeur)
    admin.select_option('#f-sexe', 'F')
    admin.select_option('#f-structure_id', str(id_thiaroye))
    admin.locator('button[form=f-fac]').click()
    attendre(admin)
    aller(admin, '/facilitateur?recherche=E2E')
    verifier('N14', 'Texte saisi contenant du code affiché comme du texte', not alertes and '<script>alert(1)</script>' in admin.locator('main').inner_text())

    # ---------------- Scénarios fonctionnels ----------------
    ctx_p, pikine = connexion(nav, PIKINE)
    aller(pikine, '/structures/create')
    pikine.fill('#nom', 'Classe passerelle de recette')
    pikine.select_option('#type', label='Classe passerelle')
    pikine.select_option('#region', 'DAKAR')
    pikine.fill('#dep', 'Pikine'); pikine.fill('#commune', 'Thiaroye'); pikine.fill('#quartier', 'Recette')
    pikine.select_option('#langue', 'Wolof')
    pikine.click('button[type=submit]'); pikine.wait_for_url(re.compile(r'/structures/\d+$')); attendre(pikine)
    code = (re.search(r'\b4\d{9}\b', pikine.locator('main').inner_text()) or [None])[0]
    verifier('F01', 'Créer une structure de type classe passerelle', code and code.endswith('3'), code or 'aucun code')

    aller(pikine, f'/structures/{id_thiaroye}/edit')
    pikine.fill('#commune', 'Guinaw Rails')
    verifier('F02', "Modifier la commune d'une structure : avertissement", pikine.locator('[role=alert]').filter(has_text='Recodification').is_visible())

    aller(admin, '/classes')
    admin.get_by_role('button', name=re.compile('Nouvelle classe')).click()
    admin.fill('#f-nom_classe', 'Classe de recette')
    admin.select_option('#f-structure_id', str(id_thiaroye))
    admin.select_option('#f-niveau', 'CE1')
    admin.locator('button[form=f-classe]').click(); attendre(admin)
    texte = admin.locator('main').inner_text()
    verifier('F03', 'Créer une classe : code généré', re.search(re.escape(code_avant) + r'-CLASSE-PASSERELLE\d+', texte))

    aller(admin, f'/selection?structure_id={id_thiaroye}&places=5&date=2026-11-02')
    t = admin.locator('main').inner_text()
    n_ret, n_att, n_eca = (int(x) for x in admin.locator('.chiffre strong').all_inner_texts()[:3])
    verifier('F04', 'Classement avec 5 places', (n_ret, n_att, n_eca) == (5, 3, 2), f'{n_ret} retenus, {n_att} en attente, {n_eca} écartés')

    aller(admin, '/apprenant?recherche=Fatou')
    admin.locator('table a[href^="/apprenant/"]').first.click(); admin.wait_for_url(re.compile(r'/apprenant/\d+$')); attendre(admin)
    id_fatou = int(admin.url.rsplit('/', 1)[1])
    admin.fill('#f-libelle', 'Contrôle de recette'); admin.fill('#f-note1', '9'); admin.fill('#f-note2', '11'); admin.fill('#f-composition', '10.5')
    admin.get_by_role('button', name='Enregistrer la note').click(); attendre(admin)
    ligne = admin.locator('tr', has_text='Contrôle de recette').inner_text()
    verifier('F05', 'Saisir les notes 9, 11 et 10,5 : moyenne 10,25', '10,25' in ligne, ligne.replace('\t', ' | '))

    aller(pikine, '/dashboard')
    t = pikine.locator('main').inner_text()
    verifier('F06', "Tableau de bord d'un agent d'IEF limité à son IEF", 'IEF Pikine' in t and 'Mbour' not in t)

    import subprocess
    sortie = subprocess.run(['php', 'artisan', 'motdepasse', compte(MBOUR)], capture_output=True, text=True, cwd=__file__.rsplit('/tests/', 1)[0]).stdout
    temporaire = re.search(r': (\S+)\n', sortie).group(1)
    c, pg = connexion(nav, MBOUR, temporaire)
    verifier('F07', 'Première connexion avec un mot de passe temporaire', pg.url.endswith('/password'))
    pg.fill('#f-current_password', temporaire); pg.fill('#f-password', MDP); pg.fill('#f-password_confirmation', MDP)
    pg.get_by_role('button', name='Enregistrer').click(); attendre(pg)
    verifier('F07b', 'Nouveau mot de passe accepté, accès au tableau de bord', pg.url.endswith('/dashboard')); c.close()

    aller(admin, '/journal?action=ACCES_REFUSE')
    r = aller(mbour, f'/apprenant/{id_fatou}')
    aller(admin, '/journal?action=ACCES_REFUSE')
    premiere = admin.locator('tbody tr').first.inner_text()
    verifier('F08', 'Accès hors périmètre refusé et historisé', r.status == 404 and 'ACCES_REFUSE' in premiere.upper().replace(' ', '_') or 'Accès refusé' in premiere, premiere.replace('\t', ' | ')[:90])

    # ---------------- Temps de réponse ----------------
    print('\nTemps de chargement complet de la page, du clic à l’événement load (médiane de 5 mesures, compte national)')
    mesures = {}
    for url in ['/dashboard', '/structures', f'/structures/{id_thiaroye}', '/classes', '/apprenant', f'/selection?structure_id={id_thiaroye}&places=5', '/journal']:
        d = []
        for _ in range(5):
            aller(admin, url)
            d.append(admin.evaluate("() => performance.getEntriesByType('navigation')[0].loadEventEnd"))
        mesures[url] = statistics.median(d)
        print(f'  {url:45} {mesures[url]:6.0f} ms')
    nav.close()

echoues = [r for r in resultats if not r[2]]
print(f'\n{len(resultats) - len(echoues)}/{len(resultats)} contrôles conformes')
sys.exit(1 if echoues else 0)

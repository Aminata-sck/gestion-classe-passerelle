export const etats = {
    EN_ATTENTE: { libelle: 'En attente', ton: 'warning' },
    VALIDE: { libelle: 'Validée', ton: 'info' },
    OUVERT: { libelle: 'Ouverte', ton: 'success' },
    GELE: { libelle: 'Gelée', ton: 'muted' },
    FERME: { libelle: 'Fermée', ton: 'danger' },
};

export const nombre = (n, decimales = 0) =>
    n === null || n === undefined ? '—' : Number(n).toLocaleString('fr-FR', { minimumFractionDigits: decimales, maximumFractionDigits: decimales });

export const note = (n) => (n === null || n === undefined ? '—' : Number(n).toLocaleString('fr-FR', { maximumFractionDigits: 2 }));

export const initiales = (nom = '') =>
    nom.split(/\s+/).filter(Boolean).slice(0, 2).map((m) => m[0]).join('').toUpperCase();

export const dateLongue = (d = new Date()) =>
    d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

/** Le profil a-t-il le droit menu:sousMenu:action ? */
export const peut = (page, menu, sousMenu, action) => (page.props.auth?.droits ?? []).includes(`${menu}:${sousMenu}:${action}`);

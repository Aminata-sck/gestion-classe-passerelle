/** Champs du dossier d'un apprenant (création et modification). */
export const viergeApprenant = {
  prenom: '', nom: '', sexe: 'F', date_naissance: '', lieu_naissance: '', situation: 'Descolarise', derniere_classe: '',
  cause_descolarisation: '', region: '', departement: '', commune: '', nom_parent: '', telephone_parent: '', handicap: 'non',
  situation_familiale: '', structure_id: '', classe_id: '',
};

export function champsApprenant(options, form) {
  return [
    { nom: 'prenom', label: 'Prénom', requis: true, col: 'c4' },
    { nom: 'nom', label: 'Nom', requis: true, col: 'c4' },
    { nom: 'sexe', label: 'Sexe', type: 'select', requis: true, col: 'c4', sansVide: true, options: [{ valeur: 'F', libelle: 'Fille' }, { valeur: 'M', libelle: 'Garçon' }] },
    { nom: 'date_naissance', label: 'Date de naissance', type: 'date', requis: true, col: 'c4' },
    { nom: 'lieu_naissance', label: 'Lieu de naissance', col: 'c4' },
    { nom: 'situation', label: 'Situation scolaire', type: 'select', requis: true, col: 'c4', sansVide: true, options: Object.entries(options.situations).map(([v, l]) => ({ valeur: v, libelle: l })) },
    { nom: 'derniere_classe', label: 'Dernière classe fréquentée', col: 'c4' },
    { nom: 'handicap', label: 'Handicap', type: 'select', col: 'c4', sansVide: true, options: [{ valeur: 'non', libelle: 'Non' }, { valeur: 'oui', libelle: 'Oui' }] },
    { nom: 'situation_familiale', label: 'Situation familiale', type: 'select', col: 'c4', options: options.situationsFamiliales.map((s) => ({ valeur: s, libelle: s })) },
    { nom: 'cause_descolarisation', label: 'Cause de la déscolarisation', type: 'textarea', col: 'c12', max: 2000 },
    { nom: 'region', label: 'Région', col: 'c4' },
    { nom: 'departement', label: 'Département', col: 'c4' },
    { nom: 'commune', label: 'Commune', col: 'c4' },
    { nom: 'nom_parent', label: 'Parent ou tuteur', col: 'c6' },
    { nom: 'telephone_parent', label: 'Téléphone du parent', type: 'tel', col: 'c6' },
    { nom: 'structure_id', label: 'Structure', type: 'select', requis: true, col: 'c6', options: options.structures.map((s) => ({ valeur: s.id, libelle: s.nom_structure })) },
    { nom: 'classe_id', label: 'Classe', type: 'select', col: 'c6', placeholder: '— Pas encore affecté —', options: options.classes.filter((c) => Number(c.structure_id) === Number(form.structure_id)).map((c) => ({ valeur: c.id, libelle: `${c.code_classe} — ${c.nom_classe}` })) },
  ];
}

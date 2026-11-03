<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Champs from '../../Components/Champs.vue';
import Confirmation from '../../Components/Confirmation.vue';
import EnTete from '../../Components/EnTete.vue';
import Fenetre from '../../Components/Fenetre.vue';
import Icone from '../../Components/Icone.vue';
import Pagination from '../../Components/Pagination.vue';
import { useEdition } from '../../lib/edition';
import { useFiltres } from '../../lib/filtres';

const props = defineProps({ utilisateurs: Object, filtres: Object, options: Object, droits: Object, moi: Number });
const filtres = useFiltres({ recherche: props.filtres.recherche ?? '' }, '/users');
const { form, ouverte, id, ouvrir, fermer, envoyer } = useEdition({ nom: '', prenom: '', ine: '', email: '', role_id: '', niveau_acces: 'IEF', ia: '', ief: '', structure_id: '' }, '/users');
const action = ref(null);
const recherche = ref('');
const etatRecherche = ref('');

const iaChoisie = computed(() => props.options.ias.find((i) => i.libelle_structure === form.ia));
const libellesNiveau = { NATIONAL: 'National (DALN, SIMEN)', IA: "Inspection d'académie", IEF: 'Inspection (IEF)', STRUCTURE: 'Une structure' };
const champs = computed(() => {
  const liste = [
    { nom: 'prenom', label: 'Prénom', col: 'c4' },
    { nom: 'nom', label: 'Nom', requis: true, col: 'c4' },
    { nom: 'ine', label: 'IEN', col: 'c4' },
    { nom: 'email', label: 'E-mail', type: 'email', requis: true, col: 'c6' },
    { nom: 'role_id', label: 'Profil', type: 'select', requis: true, col: 'c6', options: props.options.roles.map((r) => ({ valeur: r.id, libelle: r.nom_role })) },
    { nom: 'niveau_acces', label: "Niveau d'accès (périmètre)", type: 'select', requis: true, col: 'c12', sansVide: true, options: props.options.niveaux.map((n) => ({ valeur: n, libelle: libellesNiveau[n] })), aide: 'Le profil fixe les actions permises ; le niveau d’accès fixe les données visibles.' },
  ];
  if (['IA', 'IEF'].includes(form.niveau_acces)) liste.push({ nom: 'ia', label: "Inspection d'académie", type: 'select', requis: true, col: 'c6', options: props.options.ias.map((i) => ({ valeur: i.libelle_structure, libelle: i.libelle_structure })) });
  if (form.niveau_acces === 'IEF') liste.push({ nom: 'ief', label: 'IEF', type: 'select', requis: true, col: 'c6', options: props.options.iefs.filter((i) => !iaChoisie.value || i.id_parent_atlas === iaChoisie.value.id_atlas).map((i) => ({ valeur: i.libelle_structure, libelle: i.libelle_structure })) });
  if (form.niveau_acces === 'STRUCTURE') liste.push({ nom: 'structure_id', label: 'Structure', type: 'select', requis: true, col: 'c12', options: props.options.structures.map((s) => ({ valeur: s.id, libelle: s.nom_structure })) });
  return liste;
});

const rechercher = async () => {
  etatRecherche.value = 'Recherche…';
  const r = await fetch(`/users/personnel?q=${encodeURIComponent(recherche.value)}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
  const d = await r.json();
  if (!r.ok) { etatRecherche.value = d.message ?? 'Agent introuvable.'; return; }
  Object.assign(form, { nom: d.nom ?? form.nom, prenom: d.prenom ?? form.prenom, ine: d.ine ?? form.ine, email: d.email ?? form.email });
  etatRecherche.value = 'Agent trouvé : les champs ont été remplis.';
};
</script>

<template>
  <Head title="Utilisateurs" />
  <EnTete fil="Administration" titre="Utilisateurs" texte="Chaque compte a un profil (les actions) et un périmètre (les données).">
    <button v-if="droits.ajouter" class="btn btn-principal" @click="ouvrir(); recherche = ''; etatRecherche = ''"><Icone nom="plus" /> Nouveau compte</button>
  </EnTete>
  <section class="carte">
    <div class="filtres"><label class="filtre large">Rechercher<input v-model="filtres.recherche" type="search" placeholder="Nom, prénom ou e-mail"></label></div>
    <div class="tableau-conteneur">
      <table class="tableau">
        <thead><tr><th>Utilisateur</th><th>Profil</th><th>Niveau</th><th>Périmètre</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          <tr v-for="u in utilisateurs.data" :key="u.id">
            <td><strong>{{ u.prenom }} {{ u.nom }}</strong><div class="secondaire">{{ u.email }}</div></td>
            <td>{{ u.profil }}</td>
            <td><span class="badge badge-info">{{ u.niveau_acces }}</span></td>
            <td>{{ u.perimetre ?? '—' }}</td>
            <td><span :class="['badge', u.status ? 'badge-success' : 'badge-danger']">{{ u.status ? 'Actif' : 'Bloqué' }}</span>
              <span v-if="u.doit_changer" class="badge badge-warning" style="margin-left:.3rem">mot de passe temporaire</span></td>
            <td class="acts">
              <template v-if="droits.modifier">
                <button class="btn btn-sm" aria-label="Modifier" @click="ouvrir({ ...u, prenom: u.prenom ?? '', ine: u.ine ?? '', ia: u.ia ?? '', ief: u.ief ?? '', structure_id: u.structure_id ?? '' })"><Icone nom="crayon" :taille="15" /></button>
                <button class="btn btn-sm" aria-label="Réinitialiser le mot de passe" title="Réinitialiser le mot de passe" @click="action = { u, type: 'reset' }"><Icone nom="cle" :taille="15" /></button>
                <button v-if="u.id !== moi" class="btn btn-sm" :title="u.status ? 'Bloquer' : 'Débloquer'" @click="action = { u, type: 'blocage' }"><Icone nom="cadenas" :taille="15" /></button>
              </template>
              <button v-if="droits.supprimer && u.id !== moi" class="btn btn-sm btn-lien-danger" aria-label="Supprimer" @click="action = { u, type: 'suppression' }"><Icone nom="corbeille" :taille="15" /></button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination :meta="utilisateurs.meta" />
  </section>

  <Fenetre v-if="ouverte" :titre="id ? 'Modifier le compte' : 'Nouveau compte'" taille="large" @fermer="fermer">
    <div v-if="!id" class="filtres" style="border-bottom:1px solid var(--trait);padding-bottom:1rem">
      <label class="filtre large">Rechercher l'agent par son IEN ou son e-mail<input v-model="recherche" type="search" @keydown.enter.prevent="rechercher"></label>
      <button type="button" class="btn" :disabled="!recherche" @click="rechercher"><Icone nom="recherche" :taille="15" /> Rechercher</button>
      <span class="secondaire" style="width:100%">{{ etatRecherche }}</span>
    </div>
    <form id="f-user" @submit.prevent="envoyer"><Champs :form="form" :champs="champs" /></form>
    <p v-if="!id" class="secondaire" style="margin:1rem 0 0">Un mot de passe temporaire est généré et envoyé par e-mail ; il devra être changé à la première connexion.</p>
    <template #pied><button class="btn" @click="fermer">Annuler</button><button class="btn btn-principal" form="f-user" :disabled="form.processing">Enregistrer</button></template>
  </Fenetre>

  <Confirmation v-if="action?.type === 'suppression'" titre="Supprimer le compte" :url="`/users/${action.u.id}`" :message="`Supprimer le compte de ${action.u.email} ?`" @fermer="action = null" />
  <Confirmation v-if="action?.type === 'blocage'" :titre="action.u.status ? 'Bloquer le compte' : 'Débloquer le compte'" methode="post" :libelle="action.u.status ? 'Bloquer' : 'Débloquer'" :url="`/users/${action.u.id}/blocage`" :message="action.u.status ? `${action.u.email} ne pourra plus se connecter.` : `${action.u.email} pourra de nouveau se connecter.`" @fermer="action = null" />
  <Confirmation v-if="action?.type === 'reset'" titre="Réinitialiser le mot de passe" methode="post" libelle="Réinitialiser" :url="`/users/${action.u.id}/reinitialisation`" :message="`Un nouveau mot de passe temporaire sera généré pour ${action.u.email}.`" @fermer="action = null" />
</template>

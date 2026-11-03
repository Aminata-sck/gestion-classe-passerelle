<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Champs from '../../Components/Champs.vue';
import Confirmation from '../../Components/Confirmation.vue';
import EnTete from '../../Components/EnTete.vue';
import Fenetre from '../../Components/Fenetre.vue';
import Icone from '../../Components/Icone.vue';
import Pagination from '../../Components/Pagination.vue';
import { useEdition } from '../../lib/edition';
import { useFiltres } from '../../lib/filtres';

const props = defineProps({ superviseurs: Object, filtres: Object, droits: Object });
const filtres = useFiltres({ recherche: props.filtres.recherche ?? '' }, '/superviseur');
const vierge = { matricule: '', prenom: '', nom: '', sexe: 'M', telephone: '', email: '', fonction: 'IEF', structure_affectation: '', region: '', departement: '', date_affectation: '', statut: 'actif' };
const { form, ouverte, id, ouvrir, fermer, envoyer } = useEdition(vierge, '/superviseur');
const aSupprimer = ref(null);
const champs = [
  { nom: 'matricule', label: 'Matricule', requis: true, col: 'c4' },
  { nom: 'prenom', label: 'Prénom', requis: true, col: 'c4' },
  { nom: 'nom', label: 'Nom', requis: true, col: 'c4' },
  { nom: 'sexe', label: 'Sexe', type: 'select', col: 'c4', sansVide: true, options: [{ valeur: 'F', libelle: 'Femme' }, { valeur: 'M', libelle: 'Homme' }] },
  { nom: 'telephone', label: 'Téléphone', type: 'tel', col: 'c4' },
  { nom: 'email', label: 'E-mail', type: 'email', col: 'c4' },
  { nom: 'fonction', label: 'Niveau', type: 'select', requis: true, col: 'c4', sansVide: true, options: ['IEF', 'IA', 'Central'].map((v) => ({ valeur: v, libelle: v })) },
  { nom: 'structure_affectation', label: "Structure d'affectation", col: 'c8' },
  { nom: 'region', label: 'Région', col: 'c4' },
  { nom: 'departement', label: 'Département', col: 'c4' },
  { nom: 'date_affectation', label: "Date d'affectation", type: 'date', col: 'c4' },
  { nom: 'statut', label: 'Statut', type: 'select', col: 'c4', sansVide: true, options: [{ valeur: 'actif', libelle: 'Actif' }, { valeur: 'inactif', libelle: 'Inactif' }] },
];
</script>

<template>
  <Head title="Superviseurs" />
  <EnTete titre="Superviseurs" texte="Agents chargés du suivi pédagogique des classes.">
    <button v-if="droits.ajouter" class="btn btn-principal" @click="ouvrir()"><Icone nom="plus" /> Nouveau superviseur</button>
  </EnTete>
  <section class="carte">
    <div class="filtres"><label class="filtre large">Rechercher<input v-model="filtres.recherche" type="search" placeholder="Nom, prénom ou matricule"></label></div>
    <div class="tableau-conteneur">
      <table class="tableau">
        <thead><tr><th>Matricule</th><th>Superviseur</th><th>Niveau</th><th>Affectation</th><th>Téléphone</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          <tr v-for="s in superviseurs.data" :key="s.id">
            <td><code>{{ s.matricule }}</code></td><td><strong>{{ s.prenom }} {{ s.nom }}</strong></td><td>{{ s.fonction }}</td>
            <td>{{ s.structure_affectation ?? '—' }}</td><td>{{ s.telephone ?? '—' }}</td>
            <td><span :class="['badge', s.statut === 'actif' ? 'badge-success' : 'badge-muted']">{{ s.statut === 'actif' ? 'Actif' : 'Inactif' }}</span></td>
            <td class="acts">
              <button v-if="droits.modifier" class="btn btn-sm" aria-label="Modifier" @click="ouvrir(s)"><Icone nom="crayon" :taille="15" /></button>
              <button v-if="droits.supprimer" class="btn btn-sm btn-lien-danger" aria-label="Supprimer" @click="aSupprimer = s"><Icone nom="corbeille" :taille="15" /></button>
            </td>
          </tr>
          <tr v-if="!superviseurs.data.length"><td colspan="7" class="vide">Aucun superviseur.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :meta="superviseurs.meta" />
  </section>
  <Fenetre v-if="ouverte" :titre="id ? 'Modifier le superviseur' : 'Nouveau superviseur'" taille="large" @fermer="fermer">
    <form id="f-sup" @submit.prevent="envoyer"><Champs :form="form" :champs="champs" /></form>
    <template #pied><button class="btn" @click="fermer">Annuler</button><button class="btn btn-principal" form="f-sup" :disabled="form.processing">Enregistrer</button></template>
  </Fenetre>
  <Confirmation v-if="aSupprimer" titre="Supprimer le superviseur" :url="`/superviseur/${aSupprimer.id}`" :message="`Supprimer ${aSupprimer.prenom} ${aSupprimer.nom} ?`" @fermer="aSupprimer = null" />
</template>

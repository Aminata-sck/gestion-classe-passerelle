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

const props = defineProps({ facilitateurs: Object, filtres: Object, options: Object, droits: Object });
const filtres = useFiltres({ recherche: props.filtres.recherche ?? '' }, '/facilitateur');
const vierge = { matricule: '', prenom: '', nom: '', sexe: 'F', date_naissance: '', telephone: '', email: '', adresse: '', niveau_etude: '', specialite: '', date_recrutement: '', type_contrat: '', structure_id: '', statut: 'actif' };
const { form, ouverte, id, ouvrir, fermer, envoyer } = useEdition(vierge, '/facilitateur');
const aSupprimer = ref(null);
const champs = [
  { nom: 'matricule', label: 'Matricule', requis: true, col: 'c4' },
  { nom: 'prenom', label: 'Prénom', requis: true, col: 'c4' },
  { nom: 'nom', label: 'Nom', requis: true, col: 'c4' },
  { nom: 'sexe', label: 'Sexe', type: 'select', requis: true, col: 'c4', sansVide: true, options: [{ valeur: 'F', libelle: 'Femme' }, { valeur: 'M', libelle: 'Homme' }] },
  { nom: 'date_naissance', label: 'Date de naissance', type: 'date', requis: true, col: 'c4' },
  { nom: 'telephone', label: 'Téléphone', type: 'tel', requis: true, col: 'c4' },
  { nom: 'email', label: 'E-mail', type: 'email', col: 'c6' },
  { nom: 'adresse', label: 'Adresse', col: 'c6' },
  { nom: 'niveau_etude', label: "Niveau d'étude", col: 'c4' },
  { nom: 'specialite', label: 'Spécialité', col: 'c4' },
  { nom: 'type_contrat', label: 'Type de contrat', requis: true, col: 'c4' },
  { nom: 'structure_id', label: 'Structure', type: 'select', requis: true, col: 'c6', options: props.options.structures.map((s) => ({ valeur: s.id, libelle: s.nom_structure })) },
  { nom: 'date_recrutement', label: 'Date de recrutement', type: 'date', requis: true, col: 'c3' },
  { nom: 'statut', label: 'Statut', type: 'select', col: 'c3', sansVide: true, options: [{ valeur: 'actif', libelle: 'Actif' }, { valeur: 'inactif', libelle: 'Inactif' }] },
];
</script>

<template>
  <Head title="Facilitateurs" />
  <EnTete titre="Facilitateurs" texte="Personnes qui tiennent les classes passerelles.">
    <button v-if="droits.ajouter" class="btn btn-principal" @click="ouvrir()"><Icone nom="plus" /> Nouveau facilitateur</button>
  </EnTete>
  <section class="carte">
    <div class="filtres"><label class="filtre large">Rechercher<input v-model="filtres.recherche" type="search" placeholder="Nom, prénom ou matricule"></label></div>
    <div class="tableau-conteneur">
      <table class="tableau">
        <thead><tr><th>Matricule</th><th>Facilitateur</th><th>Téléphone</th><th>Structure</th><th>Contrat</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          <tr v-for="f in facilitateurs.data" :key="f.id">
            <td><code>{{ f.matricule }}</code></td>
            <td><strong>{{ f.prenom }} {{ f.nom }}</strong><div class="secondaire">{{ f.niveau_etude }}</div></td>
            <td>{{ f.telephone }}</td>
            <td>{{ f.structure }}</td>
            <td>{{ f.type_contrat }}</td>
            <td><span :class="['badge', f.statut === 'actif' ? 'badge-success' : 'badge-muted']">{{ f.statut === 'actif' ? 'Actif' : 'Inactif' }}</span></td>
            <td class="acts">
              <button v-if="droits.modifier" class="btn btn-sm" aria-label="Modifier" @click="ouvrir(f)"><Icone nom="crayon" :taille="15" /></button>
              <button v-if="droits.supprimer" class="btn btn-sm btn-lien-danger" aria-label="Supprimer" @click="aSupprimer = f"><Icone nom="corbeille" :taille="15" /></button>
            </td>
          </tr>
          <tr v-if="!facilitateurs.data.length"><td colspan="7" class="vide">Aucun facilitateur.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :meta="facilitateurs.meta" />
  </section>
  <Fenetre v-if="ouverte" :titre="id ? 'Modifier le facilitateur' : 'Nouveau facilitateur'" taille="large" @fermer="fermer">
    <form id="f-fac" @submit.prevent="envoyer"><Champs :form="form" :champs="champs" /></form>
    <template #pied><button class="btn" @click="fermer">Annuler</button><button class="btn btn-principal" form="f-fac" :disabled="form.processing">Enregistrer</button></template>
  </Fenetre>
  <Confirmation v-if="aSupprimer" titre="Supprimer le facilitateur" :url="`/facilitateur/${aSupprimer.id}`" :message="`Supprimer ${aSupprimer.prenom} ${aSupprimer.nom} ?`" @fermer="aSupprimer = null" />
</template>

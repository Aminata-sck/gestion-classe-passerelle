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

const props = defineProps({ operateurs: Object, filtres: Object, droits: Object });
const filtres = useFiltres({ recherche: props.filtres.recherche ?? '' }, '/operateur');
const vierge = { code_operateur: '', nom_organisation: '', type_operateur: '', prenom_responsable: '', nom_responsable: '', telephone: '', email: '', adresse: '', numero_agrement: '', date_debut: '', date_fin: '', statut: 'actif' };
const { form, ouverte, id, ouvrir, fermer, envoyer } = useEdition(vierge, '/operateur');
const aSupprimer = ref(null);
const champs = [
  { nom: 'code_operateur', label: 'Code', requis: true, col: 'c4' },
  { nom: 'nom_organisation', label: 'Organisation', requis: true, col: 'c8' },
  { nom: 'type_operateur', label: 'Type', type: 'select', requis: true, col: 'c4', options: ['Association', 'ONG', 'GIE', 'OSC', 'Collectivité territoriale', 'Autre'].map((v) => ({ valeur: v, libelle: v })) },
  { nom: 'prenom_responsable', label: 'Prénom du responsable', requis: true, col: 'c4' },
  { nom: 'nom_responsable', label: 'Nom du responsable', requis: true, col: 'c4' },
  { nom: 'telephone', label: 'Téléphone', type: 'tel', requis: true, col: 'c4' },
  { nom: 'email', label: 'E-mail', type: 'email', col: 'c4' },
  { nom: 'numero_agrement', label: "Numéro d'agrément", col: 'c4' },
  { nom: 'adresse', label: 'Adresse', col: 'c12' },
  { nom: 'date_debut', label: 'Début', type: 'date', requis: true, col: 'c4' },
  { nom: 'date_fin', label: 'Fin', type: 'date', col: 'c4' },
  { nom: 'statut', label: 'Statut', type: 'select', col: 'c4', sansVide: true, options: [{ valeur: 'actif', libelle: 'Actif' }, { valeur: 'inactif', libelle: 'Inactif' }] },
];
</script>

<template>
  <Head title="Opérateurs" />
  <EnTete titre="Opérateurs" texte="Organisations qui gèrent les structures et les facilitateurs.">
    <button v-if="droits.ajouter" class="btn btn-principal" @click="ouvrir()"><Icone nom="plus" /> Nouvel opérateur</button>
  </EnTete>
  <section class="carte">
    <div class="filtres"><label class="filtre large">Rechercher<input v-model="filtres.recherche" type="search" placeholder="Organisation ou code"></label></div>
    <div class="tableau-conteneur">
      <table class="tableau">
        <thead><tr><th>Code</th><th>Organisation</th><th>Type</th><th>Responsable</th><th>Téléphone</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          <tr v-for="o in operateurs.data" :key="o.id">
            <td><code>{{ o.code_operateur }}</code></td><td><strong>{{ o.nom_organisation }}</strong></td><td>{{ o.type_operateur }}</td>
            <td>{{ o.prenom_responsable }} {{ o.nom_responsable }}</td><td>{{ o.telephone }}</td>
            <td><span :class="['badge', o.statut === 'actif' ? 'badge-success' : 'badge-muted']">{{ o.statut === 'actif' ? 'Actif' : 'Inactif' }}</span></td>
            <td class="acts">
              <button v-if="droits.modifier" class="btn btn-sm" aria-label="Modifier" @click="ouvrir(o)"><Icone nom="crayon" :taille="15" /></button>
              <button v-if="droits.supprimer" class="btn btn-sm btn-lien-danger" aria-label="Supprimer" @click="aSupprimer = o"><Icone nom="corbeille" :taille="15" /></button>
            </td>
          </tr>
          <tr v-if="!operateurs.data.length"><td colspan="7" class="vide">Aucun opérateur.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :meta="operateurs.meta" />
  </section>
  <Fenetre v-if="ouverte" :titre="id ? 'Modifier l’opérateur' : 'Nouvel opérateur'" taille="large" @fermer="fermer">
    <form id="f-op" @submit.prevent="envoyer"><Champs :form="form" :champs="champs" /></form>
    <template #pied><button class="btn" @click="fermer">Annuler</button><button class="btn btn-principal" form="f-op" :disabled="form.processing">Enregistrer</button></template>
  </Fenetre>
  <Confirmation v-if="aSupprimer" titre="Supprimer l'opérateur" :url="`/operateur/${aSupprimer.id}`" :message="`Supprimer ${aSupprimer.nom_organisation} ?`" @fermer="aSupprimer = null" />
</template>

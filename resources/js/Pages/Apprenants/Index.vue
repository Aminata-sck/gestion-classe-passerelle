<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Champs from '../../Components/Champs.vue';
import EnTete from '../../Components/EnTete.vue';
import Fenetre from '../../Components/Fenetre.vue';
import Icone from '../../Components/Icone.vue';
import Pagination from '../../Components/Pagination.vue';
import { champsApprenant, viergeApprenant } from '../../lib/apprenant';
import { useEdition } from '../../lib/edition';
import { useFiltres } from '../../lib/filtres';

const props = defineProps({ apprenants: Object, filtres: Object, options: Object, droits: Object });
const filtres = useFiltres({ recherche: props.filtres.recherche ?? '', structure_id: props.filtres.structure_id ?? '', sexe: props.filtres.sexe ?? '', situation: props.filtres.situation ?? '' }, '/apprenant');
const { form, ouverte, ouvrir, fermer, envoyer } = useEdition(viergeApprenant, '/apprenant');
const champs = computed(() => champsApprenant(props.options, form));
</script>

<template>
  <Head title="Apprenants" />
  <EnTete titre="Apprenants" texte="Enfants hors école enrôlés dans les structures de votre périmètre.">
    <Link href="/selection" class="btn"><Icone nom="selection" :taille="15" /> Classement de sélection</Link>
    <button v-if="droits.ajouter" class="btn btn-principal" @click="ouvrir()"><Icone nom="plus" /> Enrôler un enfant</button>
  </EnTete>
  <section class="carte">
    <div class="filtres">
      <label class="filtre large">Rechercher<input v-model="filtres.recherche" type="search" placeholder="Nom ou prénom"></label>
      <label class="filtre">Structure<select v-model="filtres.structure_id"><option value="">Toutes</option><option v-for="s in options.structures" :key="s.id" :value="s.id">{{ s.nom_structure }}</option></select></label>
      <label class="filtre">Sexe<select v-model="filtres.sexe"><option value="">Tous</option><option value="F">Filles</option><option value="M">Garçons</option></select></label>
      <label class="filtre">Situation<select v-model="filtres.situation"><option value="">Toutes</option><option v-for="(l, v) in options.situations" :key="v" :value="v">{{ l }}</option></select></label>
    </div>
    <div class="tableau-conteneur">
      <table class="tableau">
        <thead><tr><th>Apprenant</th><th>Sexe</th><th class="num">Âge</th><th>Situation</th><th>Structure</th><th>Classe</th><th class="num">Évaluations</th><th></th></tr></thead>
        <tbody>
          <tr v-for="a in apprenants.data" :key="a.id">
            <td><Link :href="`/apprenant/${a.id}`"><strong>{{ a.prenom }} {{ a.nom }}</strong></Link>
              <span v-if="a.vulnerable" class="badge badge-warning" style="margin-left:.4rem">vulnérable</span></td>
            <td>{{ a.sexe === 'F' ? 'Fille' : 'Garçon' }}</td>
            <td class="num">{{ a.age ?? '—' }}</td>
            <td>{{ a.situation }}</td>
            <td>{{ a.structure ?? '—' }}</td>
            <td><code v-if="a.classe">{{ a.classe }}</code><span v-else class="secondaire">non affecté</span></td>
            <td class="num">{{ a.evaluations }}</td>
            <td class="acts"><Link :href="`/apprenant/${a.id}`" class="btn btn-sm">Fiche</Link></td>
          </tr>
          <tr v-if="!apprenants.data.length"><td colspan="8" class="vide">Aucun apprenant.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :meta="apprenants.meta" />
  </section>
  <Fenetre v-if="ouverte" titre="Enrôler un enfant" taille="large" @fermer="fermer">
    <form id="f-app" @submit.prevent="envoyer"><Champs :form="form" :champs="champs" /></form>
    <template #pied><button class="btn" @click="fermer">Annuler</button><button class="btn btn-principal" form="f-app" :disabled="form.processing">Enregistrer</button></template>
  </Fenetre>
</template>

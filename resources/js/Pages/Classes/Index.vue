<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Champs from '../../Components/Champs.vue';
import Confirmation from '../../Components/Confirmation.vue';
import EnTete from '../../Components/EnTete.vue';
import Fenetre from '../../Components/Fenetre.vue';
import Icone from '../../Components/Icone.vue';
import Pagination from '../../Components/Pagination.vue';
import { useEdition } from '../../lib/edition';
import { useFiltres } from '../../lib/filtres';

const props = defineProps({ classes: Object, filtres: Object, options: Object, droits: Object });
const filtres = useFiltres({ recherche: props.filtres.recherche ?? '', niveau: props.filtres.niveau ?? '' }, '/classes');
const { form, ouverte, id, ouvrir, fermer, envoyer } = useEdition({ nom_classe: '', structure_id: '', facilitateur_id: '', niveau: 'CI', langue: '', date_ouverture: '' }, '/classes');
const aSupprimer = ref(null);
const champs = computed(() => [
  { nom: 'structure_id', label: 'Structure', type: 'select', requis: true, col: 'c12', options: props.options.structures.map((s) => ({ valeur: s.id, libelle: `${s.nom_structure} (${s.code_structure})` })), aide: id.value ? 'La structure d’une classe ne change pas : son code en dépend.' : 'Le code de la classe sera généré à partir du code de la structure.' },
  { nom: 'nom_classe', label: 'Nom de la classe', requis: true, col: 'c8' },
  { nom: 'niveau', label: 'Niveau', type: 'select', requis: true, col: 'c4', sansVide: true, options: props.options.niveaux.map((n) => ({ valeur: n, libelle: n })) },
  { nom: 'facilitateur_id', label: 'Facilitateur', type: 'select', col: 'c6', vide: '', placeholder: '— Aucun —', options: props.options.facilitateurs.filter((f) => !form.structure_id || f.structure_id === Number(form.structure_id)).map((f) => ({ valeur: f.id, libelle: `${f.prenom} ${f.nom}` })) },
  { nom: 'langue', label: 'Langue', type: 'select', col: 'c3', options: props.options.langues.map((l) => ({ valeur: l, libelle: l })) },
  { nom: 'date_ouverture', label: "Date d'ouverture", type: 'date', col: 'c3' },
]);
</script>

<template>
  <Head title="Classes" />
  <EnTete titre="Classes" texte="Chaque classe reçoit un code unique, construit à partir du code de sa structure.">
    <button v-if="droits.ajouter" class="btn btn-principal" @click="ouvrir()"><Icone nom="plus" /> Nouvelle classe</button>
  </EnTete>
  <section class="carte">
    <div class="filtres">
      <label class="filtre large">Rechercher<input v-model="filtres.recherche" type="search" placeholder="Nom ou code"></label>
      <label class="filtre">Niveau<select v-model="filtres.niveau"><option value="">Tous</option><option v-for="n in options.niveaux" :key="n">{{ n }}</option></select></label>
    </div>
    <div class="tableau-conteneur">
      <table class="tableau">
        <thead><tr><th>Code</th><th>Classe</th><th>Niveau</th><th>Structure</th><th>Facilitateur</th><th class="num">Apprenants</th><th></th></tr></thead>
        <tbody>
          <tr v-for="c in classes.data" :key="c.id">
            <td><code>{{ c.code }}</code></td>
            <td><strong>{{ c.nom }}</strong></td>
            <td><span class="badge badge-info">{{ c.niveau }}</span></td>
            <td><Link :href="`/structures/${c.structure_id}`">{{ c.structure }}</Link></td>
            <td>{{ c.facilitateur ?? '—' }}</td>
            <td class="num">{{ c.apprenants }}</td>
            <td class="acts">
              <button v-if="droits.modifier" class="btn btn-sm" aria-label="Modifier" @click="ouvrir({ ...c, nom_classe: c.nom, facilitateur_id: c.facilitateur_id ?? '' })"><Icone nom="crayon" :taille="15" /></button>
              <button v-if="droits.supprimer" class="btn btn-sm btn-lien-danger" aria-label="Supprimer" @click="aSupprimer = c"><Icone nom="corbeille" :taille="15" /></button>
            </td>
          </tr>
          <tr v-if="!classes.data.length"><td colspan="7" class="vide">Aucune classe.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :meta="classes.meta" />
  </section>

  <Fenetre v-if="ouverte" :titre="id ? 'Modifier la classe' : 'Nouvelle classe'" @fermer="fermer">
    <form id="f-classe" @submit.prevent="envoyer"><Champs :form="form" :champs="champs" /></form>
    <template #pied><button class="btn" @click="fermer">Annuler</button><button class="btn btn-principal" form="f-classe" :disabled="form.processing">Enregistrer</button></template>
  </Fenetre>
  <Confirmation v-if="aSupprimer" titre="Supprimer la classe" :url="`/classes/${aSupprimer.id}`" :message="`Supprimer la classe ${aSupprimer.code} ?`" @fermer="aSupprimer = null" />
</template>

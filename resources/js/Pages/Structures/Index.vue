<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Confirmation from '../../Components/Confirmation.vue';
import EnTete from '../../Components/EnTete.vue';
import Etat from '../../Components/Etat.vue';
import Icone from '../../Components/Icone.vue';
import Pagination from '../../Components/Pagination.vue';
import { useFiltres } from '../../lib/filtres';

const props = defineProps({ structures: Object, compteurs: Object, filtres: Object, options: Object, droits: Object });
const filtres = useFiltres({ recherche: props.filtres.recherche ?? '', etat: props.filtres.etat ?? '', type_offre_id: props.filtres.type_offre_id ?? '', ief: props.filtres.ief ?? '' }, '/structures');
const aArchiver = ref(null);
</script>

<template>
  <Head title="Structures" />
  <EnTete titre="Structures" texte="Lieux qui abritent une offre de l'EBJA, codifiés selon le référentiel de la DALN.">
    <Link v-if="droits.ajouter" href="/structures/create" class="btn btn-principal"><Icone nom="plus" /> Nouvelle structure</Link>
  </EnTete>

  <div class="grille g4" style="margin-bottom:1.25rem">
    <div class="chiffre"><strong>{{ compteurs.total }}</strong>structures</div>
    <div class="chiffre"><strong>{{ compteurs.ouvertes }}</strong>ouvertes</div>
    <div class="chiffre"><strong>{{ compteurs.attente }}</strong>en attente de validation</div>
    <div class="chiffre"><strong>{{ compteurs.fermees }}</strong>gelées ou fermées</div>
  </div>

  <section class="carte">
    <div class="filtres">
      <label class="filtre large">Rechercher<input v-model="filtres.recherche" type="search" placeholder="Nom, code ou commune"></label>
      <label class="filtre">État
        <select v-model="filtres.etat"><option value="">Tous</option><option v-for="(l, e) in options.etats" :key="e" :value="e">{{ l }}</option></select>
      </label>
      <label class="filtre">Type d'offre
        <select v-model="filtres.type_offre_id"><option value="">Tous</option><option v-for="t in options.types" :key="t.id" :value="t.id">{{ t.libelle }}</option></select>
      </label>
      <label v-if="options.iefs.length > 1" class="filtre">IEF
        <select v-model="filtres.ief"><option value="">Toutes</option><option v-for="i in options.iefs" :key="i" :value="i">{{ i }}</option></select>
      </label>
    </div>
    <div class="tableau-conteneur">
      <table class="tableau">
        <thead><tr><th>Code</th><th>Structure</th><th>Type</th><th>Commune</th><th>IEF</th><th class="num">Classes</th><th class="num">Apprenants</th><th>État</th><th></th></tr></thead>
        <tbody>
          <tr v-for="s in structures.data" :key="s.id">
            <td><code>{{ s.code }}</code></td>
            <td><Link :href="`/structures/${s.id}`"><strong>{{ s.nom }}</strong></Link></td>
            <td>{{ s.type }}</td>
            <td>{{ s.commune }}</td>
            <td>{{ s.ief }}</td>
            <td class="num">{{ s.classes }}</td>
            <td class="num">{{ s.apprenants }}</td>
            <td><Etat :etat="s.etat" /></td>
            <td class="acts">
              <Link :href="`/structures/${s.id}`" class="btn btn-sm" aria-label="Voir"><Icone nom="oeil" :taille="15" /></Link>
              <Link v-if="droits.modifier" :href="`/structures/${s.id}/edit`" class="btn btn-sm" aria-label="Modifier"><Icone nom="crayon" :taille="15" /></Link>
              <button v-if="droits.supprimer" class="btn btn-sm btn-lien-danger" aria-label="Archiver" @click="aArchiver = s"><Icone nom="corbeille" :taille="15" /></button>
            </td>
          </tr>
          <tr v-if="!structures.data.length"><td colspan="9" class="vide">Aucune structure ne correspond à ces critères.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :meta="structures.meta" />
  </section>

  <Confirmation v-if="aArchiver" titre="Archiver la structure" :url="`/structures/${aArchiver.id}`" libelle="Archiver"
    :message="`La structure « ${aArchiver.nom} » sera archivée. Son code ${aArchiver.code} restera consultable dans l'historique.`" @fermer="aArchiver = null" />
</template>

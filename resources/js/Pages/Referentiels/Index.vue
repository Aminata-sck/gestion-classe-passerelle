<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Champs from '../../Components/Champs.vue';
import Confirmation from '../../Components/Confirmation.vue';
import EnTete from '../../Components/EnTete.vue';
import Fenetre from '../../Components/Fenetre.vue';
import Icone from '../../Components/Icone.vue';
import { useEdition } from '../../lib/edition';

const props = defineProps({ cle: String, titre: String, champs: Array, lignes: Array, typesOffre: Array, referentiels: Array, droits: Object });
const vierge = Object.fromEntries([...props.champs.map((c) => [c.nom, '']), ['actif', true]]);
const { form, ouverte, id, ouvrir, fermer, envoyer } = useEdition(vierge, `/configuration/${props.cle}`);
const aDesactiver = ref(null);
const champsForm = computed(() => [
  ...props.champs.map((c) => (c.type === 'type_offre'
    ? { nom: c.nom, label: c.label, type: 'select', col: 'c12', options: props.typesOffre.map((t) => ({ valeur: t.id, libelle: t.libelle })) }
    : { nom: c.nom, label: c.label, type: c.type === 'textarea' ? 'textarea' : 'text', requis: c.requis, col: 'c12' })),
  { nom: 'actif', label: 'Actif', type: 'case', col: 'c12' },
]);
const affiche = (l, c) => (c.type === 'type_offre' ? props.typesOffre.find((t) => t.id === l[c.nom])?.libelle ?? '—' : l[c.nom] ?? '—');
const colonnes = computed(() => props.champs.filter((c) => c.type !== 'textarea'));
</script>

<template>
  <Head :title="titre" />
  <EnTete fil="Configuration" :titre="titre" texte="Ces listes alimentent les formulaires : les modifier ne demande aucun changement de code.">
    <button v-if="droits.ajouter" class="btn btn-principal" @click="ouvrir()"><Icone nom="plus" /> Ajouter</button>
  </EnTete>
  <div class="actions" style="margin-bottom:1rem">
    <Link v-for="r in referentiels" :key="r.cle" :href="`/configuration/${r.cle}`" :class="['pastille-lien']" :style="r.cle === cle ? 'background:var(--vert);color:#fff;border-color:var(--vert)' : ''">{{ r.titre }}</Link>
  </div>
  <section class="carte">
    <table class="tableau">
      <thead><tr><th v-for="c in colonnes" :key="c.nom">{{ c.label.split('(')[0] }}</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <tr v-for="l in lignes" :key="l.id">
          <td v-for="(c, i) in colonnes" :key="c.nom"><strong v-if="i === 0">{{ affiche(l, c) }}</strong><template v-else>{{ affiche(l, c) }}</template></td>
          <td><span :class="['badge', l.actif ? 'badge-success' : 'badge-muted']">{{ l.actif ? 'Actif' : 'Inactif' }}</span></td>
          <td class="acts">
            <button v-if="droits.modifier" class="btn btn-sm" aria-label="Modifier" @click="ouvrir({ ...l, actif: !!l.actif })"><Icone nom="crayon" :taille="15" /></button>
            <button v-if="droits.supprimer && l.actif" class="btn btn-sm btn-lien-danger" aria-label="Désactiver" @click="aDesactiver = l"><Icone nom="corbeille" :taille="15" /></button>
          </td>
        </tr>
        <tr v-if="!lignes.length"><td :colspan="colonnes.length + 2" class="vide">Liste vide.</td></tr>
      </tbody>
    </table>
  </section>
  <Fenetre v-if="ouverte" :titre="id ? 'Modifier' : 'Ajouter'" @fermer="fermer">
    <form id="f-ref" @submit.prevent="envoyer"><Champs :form="form" :champs="champsForm" /></form>
    <template #pied><button class="btn" @click="fermer">Annuler</button><button class="btn btn-principal" form="f-ref" :disabled="form.processing">Enregistrer</button></template>
  </Fenetre>
  <Confirmation v-if="aDesactiver" titre="Désactiver" libelle="Désactiver" :url="`/configuration/${cle}/${aDesactiver.id}`" message="L’élément ne sera plus proposé dans les formulaires, mais les données existantes le conservent." @fermer="aDesactiver = null" />
</template>

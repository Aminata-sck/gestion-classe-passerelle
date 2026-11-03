<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Champs from '../../Components/Champs.vue';
import Confirmation from '../../Components/Confirmation.vue';
import EnTete from '../../Components/EnTete.vue';
import Fenetre from '../../Components/Fenetre.vue';
import Icone from '../../Components/Icone.vue';
import { useEdition } from '../../lib/edition';

const props = defineProps({ menus: Array, droits: Object });
const menu = useEdition({ nom_menu: '', icon: '', url: '', ordre: 0, statut: true }, '/menus');
const sous = useEdition({ menu_id: '', nom_sous_menu: '', url: '', icon: '', ordre: 0, statut: true }, '/sous-menus');
const aSupprimer = ref(null);
const champsMenu = [
  { nom: 'nom_menu', label: 'Nom du menu', requis: true, col: 'c8' },
  { nom: 'ordre', label: 'Ordre', type: 'number', min: 0, requis: true, col: 'c4' },
  { nom: 'url', label: 'Adresse (facultative)', col: 'c6', aide: 'Exemple : /structures' },
  { nom: 'icon', label: 'Icône', col: 'c6' },
  { nom: 'statut', label: 'Actif', type: 'case', col: 'c12' },
];
const champsSous = computed(() => [
  { nom: 'menu_id', label: 'Menu parent', type: 'select', requis: true, col: 'c6', options: props.menus.map((m) => ({ valeur: m.id, libelle: m.nom_menu })) },
  { nom: 'nom_sous_menu', label: 'Nom du sous-menu', requis: true, col: 'c6' },
  { nom: 'url', label: 'Adresse', requis: true, col: 'c6', aide: 'Exemple : /configuration/langues' },
  { nom: 'ordre', label: 'Ordre', type: 'number', min: 0, requis: true, col: 'c3' },
  { nom: 'icon', label: 'Icône', col: 'c3' },
  { nom: 'statut', label: 'Actif', type: 'case', col: 'c12' },
]);
</script>

<template>
  <Head title="Menus" />
  <EnTete fil="Administration" titre="Menus" texte="Organisation de la barre latérale. Les droits se règlent ensuite par profil.">
    <template v-if="droits.ajouter">
      <button class="btn" @click="sous.ouvrir()"><Icone nom="plus" /> Sous-menu</button>
      <button class="btn btn-principal" @click="menu.ouvrir()"><Icone nom="plus" /> Menu</button>
    </template>
  </EnTete>
  <section class="carte">
    <table class="tableau">
      <thead><tr><th>Ordre</th><th>Menu ou sous-menu</th><th>Adresse</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        <template v-for="m in menus" :key="m.id">
          <tr style="background:#f6f8f7">
            <td>{{ m.ordre }}</td><td><strong>{{ m.nom_menu }}</strong></td><td><code v-if="m.url">{{ m.url }}</code></td>
            <td><span :class="['badge', m.statut ? 'badge-success' : 'badge-muted']">{{ m.statut ? 'Actif' : 'Inactif' }}</span></td>
            <td class="acts">
              <button v-if="droits.modifier" class="btn btn-sm" aria-label="Modifier" @click="menu.ouvrir(m)"><Icone nom="crayon" :taille="15" /></button>
              <button v-if="droits.supprimer" class="btn btn-sm btn-lien-danger" aria-label="Supprimer" @click="aSupprimer = { url: `/menus/${m.id}`, nom: m.nom_menu }"><Icone nom="corbeille" :taille="15" /></button>
            </td>
          </tr>
          <tr v-for="s in m.sousMenus" :key="'s' + s.id">
            <td class="secondaire">{{ s.ordre }}</td><td style="padding-left:1.6rem">{{ s.nom_sous_menu }}</td><td><code>{{ s.url }}</code></td>
            <td><span :class="['badge', s.statut ? 'badge-success' : 'badge-muted']">{{ s.statut ? 'Actif' : 'Inactif' }}</span></td>
            <td class="acts">
              <button v-if="droits.modifier" class="btn btn-sm" aria-label="Modifier" @click="sous.ouvrir(s)"><Icone nom="crayon" :taille="15" /></button>
              <button v-if="droits.supprimer" class="btn btn-sm btn-lien-danger" aria-label="Supprimer" @click="aSupprimer = { url: `/sous-menus/${s.id}`, nom: s.nom_sous_menu }"><Icone nom="corbeille" :taille="15" /></button>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </section>
  <Fenetre v-if="menu.ouverte.value" :titre="menu.id.value ? 'Modifier le menu' : 'Nouveau menu'" @fermer="menu.fermer">
    <form id="f-menu" @submit.prevent="menu.envoyer"><Champs :form="menu.form" :champs="champsMenu" /></form>
    <template #pied><button class="btn" @click="menu.fermer">Annuler</button><button class="btn btn-principal" form="f-menu">Enregistrer</button></template>
  </Fenetre>
  <Fenetre v-if="sous.ouverte.value" :titre="sous.id.value ? 'Modifier le sous-menu' : 'Nouveau sous-menu'" @fermer="sous.fermer">
    <form id="f-sous" @submit.prevent="sous.envoyer"><Champs :form="sous.form" :champs="champsSous" /></form>
    <template #pied><button class="btn" @click="sous.fermer">Annuler</button><button class="btn btn-principal" form="f-sous">Enregistrer</button></template>
  </Fenetre>
  <Confirmation v-if="aSupprimer" titre="Supprimer" :url="aSupprimer.url" :message="`Supprimer « ${aSupprimer.nom} » ? Les droits associés seront retirés.`" @fermer="aSupprimer = null" />
</template>

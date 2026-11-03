<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Confirmation from '../../Components/Confirmation.vue';
import EnTete from '../../Components/EnTete.vue';
import Icone from '../../Components/Icone.vue';

const props = defineProps({ profils: Array, menus: Array, actions: Array, droits: Object });
const choisi = ref(props.profils[0] ?? null);
const form = useForm({ nom: choisi.value?.nom ?? '', droits: [...(choisi.value?.droits ?? [])] });
const aSupprimer = ref(null);
const choisir = (p) => { choisi.value = p; form.clearErrors(); form.nom = p ? p.nom : ''; form.droits = p ? [...p.droits] : []; };
const cle = (m, s, a) => `${m.id}:${s.id}:${a.id}`;
const ligneComplete = (m, s) => props.actions.every((a) => form.droits.includes(cle(m, s, a)));
const basculerLigne = (m, s) => {
  const toutes = props.actions.map((a) => cle(m, s, a));
  form.droits = ligneComplete(m, s) ? form.droits.filter((d) => !toutes.includes(d)) : [...new Set([...form.droits, ...toutes])];
};
const enregistrer = () => (choisi.value ? form.put(`/profils/${choisi.value.id}`, { preserveScroll: true, preserveState: true }) : form.post('/profils', { preserveScroll: true }));
</script>

<template>
  <Head title="Profils et droits" />
  <EnTete fil="Administration" titre="Profils et droits" texte="Un droit autorise une action (consulter, ajouter, modifier, supprimer) sur un écran.">
    <button v-if="droits.ajouter" class="btn btn-principal" @click="choisir(null)"><Icone nom="plus" /> Nouveau profil</button>
  </EnTete>
  <div class="grille" style="grid-template-columns:280px 1fr">
    <section class="carte" style="padding:.6rem 0">
      <div v-for="p in profils" :key="p.id" class="nav" style="padding:0">
        <a href="#" :class="{ actif: choisi?.id === p.id }" @click.prevent="choisir(p)">{{ p.nom }} <span class="secondaire" style="margin-left:auto">{{ p.utilisateurs }} compte{{ p.utilisateurs > 1 ? 's' : '' }}</span></a>
      </div>
    </section>
    <form class="carte" @submit.prevent="enregistrer">
      <div class="formulaire" style="margin-bottom:1rem">
        <div :class="['champ c8', { invalide: form.errors.nom }]"><label for="nom">Nom du profil</label><input id="nom" v-model="form.nom" required maxlength="100"><span v-if="form.errors.nom" class="erreur">{{ form.errors.nom }}</span></div>
      </div>
      <div class="tableau-conteneur">
        <table class="tableau">
          <thead><tr><th>Écran</th><th v-for="a in actions" :key="a.id" style="text-align:center">{{ a.nom_permission }}</th><th style="text-align:center">Tout</th></tr></thead>
          <tbody>
            <template v-for="m in menus" :key="m.id">
              <tr><td :colspan="actions.length + 2" style="background:#f6f8f7;font-weight:700">{{ m.nom }}</td></tr>
              <tr v-for="s in m.sousMenus" :key="s.id">
                <td style="padding-left:1.5rem">{{ s.nom }}</td>
                <td v-for="a in actions" :key="a.id" style="text-align:center"><input v-model="form.droits" type="checkbox" :value="cle(m, s, a)" :aria-label="`${a.nom_permission} — ${s.nom}`" style="width:18px;height:18px;accent-color:var(--vert)"></td>
                <td style="text-align:center"><input type="checkbox" :checked="ligneComplete(m, s)" :aria-label="`Tous les droits — ${s.nom}`" style="width:18px;height:18px;accent-color:var(--vert)" @change="basculerLigne(m, s)"></td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
      <div class="actions" style="margin-top:1.2rem;justify-content:space-between">
        <button v-if="choisi && droits.supprimer" type="button" class="btn btn-lien-danger" @click="aSupprimer = choisi">Supprimer le profil</button><span v-else></span>
        <button class="btn btn-principal" :disabled="form.processing || !(choisi ? droits.modifier : droits.ajouter)">{{ choisi ? 'Enregistrer les droits' : 'Créer le profil' }}</button>
      </div>
    </form>
  </div>
  <Confirmation v-if="aSupprimer" titre="Supprimer le profil" :url="`/profils/${aSupprimer.id}`" :message="`Supprimer le profil « ${aSupprimer.nom} » ?`" @fermer="aSupprimer = null" />
</template>

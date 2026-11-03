<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import EnTete from '../../Components/EnTete.vue';

const props = defineProps({ structure: Object, options: Object, etatsPossibles: Array, perimetre: Object });
const edition = computed(() => !!props.structure.id);
const form = useForm({ ...props.structure });
const initial = { commune: props.structure.commune, type_offre_id: props.structure.type_offre_id };

// Découpage administratif : région → IA → IEF → communes rattachées à l'IEF.
const atlas = props.options.atlas;
const parNom = (niveau, nom) => atlas.find((a) => a.niveau === niveau && a.nom === nom);
const regions = atlas.filter((a) => a.niveau === 8);
const ias = computed(() => { const r = parNom(8, form.region); return atlas.filter((a) => a.niveau === 9 && (!r || a.parent === r.id)); });
const iefs = computed(() => { const ia = parNom(9, form.ia); return ia ? atlas.filter((a) => a.niveau === 11 && a.parent === ia.id) : []; });
const communes = computed(() => {
  const ief = parNom(11, form.ief);
  if (!ief) return [];
  const arr = atlas.filter((a) => a.niveau === 12 && a.parent === ief.id).map((a) => a.id);
  return atlas.filter((a) => (a.niveau === 13 && arr.includes(a.parent)) || (a.niveau === 12 && a.parent === ief.id)).map((a) => a.nom).sort();
});
const verrouIa = props.perimetre.niveau === 'IA' || props.perimetre.niveau === 'IEF';
const verrouIef = props.perimetre.niveau === 'IEF';
watch(() => form.ia, (v, ancien) => { if (ancien && v !== ancien && !verrouIef) form.ief = ''; });

const recodification = computed(() => edition.value && (
  (form.commune || '').trim().toUpperCase() !== (initial.commune || '').trim().toUpperCase() || Number(form.type_offre_id) !== Number(initial.type_offre_id)));
const suffixe = computed(() => props.options.types.find((t) => t.id === Number(form.type_offre_id))?.suffixe);
const envoyer = () => (edition.value ? form.put(`/structures/${props.structure.id}`) : form.post('/structures'));
</script>

<template>
  <Head :title="edition ? 'Modifier la structure' : 'Nouvelle structure'" />
  <EnTete :fil="edition ? `Structures / ${structure.code}` : 'Structures'" :titre="edition ? 'Modifier la structure' : 'Nouvelle structure'"
    :texte="edition ? 'Le code de la structure ne se modifie pas : un changement de commune ou de type d’offre entraîne une recodification.' : 'Le code à dix chiffres est généré automatiquement à l’enregistrement.'">
    <Link :href="edition ? `/structures/${structure.id}` : '/structures'" class="btn">Annuler</Link>
  </EnTete>

  <form class="carte" @submit.prevent="envoyer">
    <div v-if="recodification" class="encadre" style="margin-bottom:1.2rem" role="alert">
      <strong>Recodification :</strong> vous modifiez la commune ou le type d'offre. À l'enregistrement, la structure actuelle sera archivée
      avec son code {{ structure.code }}, et une nouvelle structure sera créée avec un nouveau code. Ses classes, facilitateurs et apprenants y seront rattachés.
    </div>
    <div class="formulaire">
      <div v-if="edition" class="champ c4"><label>Code de la structure</label><input :value="structure.code" readonly></div>
      <div :class="['champ', edition ? 'c8' : 'c12', { invalide: form.errors.nom_structure }]">
        <label for="nom">Nom de la structure <span class="requis">*</span></label>
        <input id="nom" v-model="form.nom_structure" required maxlength="255">
        <span v-if="form.errors.nom_structure" class="erreur">{{ form.errors.nom_structure }}</span>
      </div>
      <div :class="['champ c6', { invalide: form.errors.type_offre_id }]">
        <label for="type">Type d'offre <span class="requis">*</span></label>
        <select id="type" v-model="form.type_offre_id" required>
          <option :value="null">— Choisir —</option>
          <option v-for="t in options.types" :key="t.id" :value="t.id">{{ t.libelle }}</option>
        </select>
        <span v-if="suffixe" class="aide">Le code se terminera par {{ suffixe }}.</span>
        <span v-if="form.errors.type_offre_id" class="erreur">{{ form.errors.type_offre_id }}</span>
      </div>
      <div :class="['champ c6', { invalide: form.errors.etat }]">
        <label for="etat">État <span class="requis">*</span></label>
        <select id="etat" v-model="form.etat" :disabled="!edition">
          <option v-for="e in etatsPossibles" :key="e" :value="e">{{ options.etats[e] }}</option>
        </select>
        <span class="aide">{{ edition ? 'Seules les transitions prévues par le cycle de vie sont proposées.' : 'Une nouvelle structure est en attente de validation par l’IEF.' }}</span>
        <span v-if="form.errors.etat" class="erreur">{{ form.errors.etat }}</span>
      </div>

      <div :class="['champ c4', { invalide: form.errors.region }]">
        <label for="region">Région <span class="requis">*</span></label>
        <select id="region" v-model="form.region" required><option value="">— Choisir —</option><option v-for="r in regions" :key="r.id" :value="r.nom">{{ r.nom }}</option></select>
        <span v-if="form.errors.region" class="erreur">{{ form.errors.region }}</span>
      </div>
      <div :class="['champ c4', { invalide: form.errors.ia }]">
        <label for="ia">Inspection d'académie <span class="requis">*</span></label>
        <select id="ia" v-model="form.ia" required :disabled="verrouIa"><option value="">— Choisir —</option><option v-for="a in ias" :key="a.id" :value="a.nom">{{ a.nom }}</option></select>
        <span v-if="form.errors.ia" class="erreur">{{ form.errors.ia }}</span>
      </div>
      <div :class="['champ c4', { invalide: form.errors.ief }]">
        <label for="ief">IEF <span class="requis">*</span></label>
        <select id="ief" v-model="form.ief" required :disabled="verrouIef"><option value="">— Choisir —</option><option v-for="a in iefs" :key="a.id" :value="a.nom">{{ a.nom }}</option><option v-if="form.ief && !iefs.some(a => a.nom === form.ief)" :value="form.ief">{{ form.ief }}</option></select>
        <span v-if="form.errors.ief" class="erreur">{{ form.errors.ief }}</span>
      </div>
      <div :class="['champ c4', { invalide: form.errors.departement }]">
        <label for="dep">Département <span class="requis">*</span></label>
        <input id="dep" v-model="form.departement" required maxlength="255">
        <span v-if="form.errors.departement" class="erreur">{{ form.errors.departement }}</span>
      </div>
      <div :class="['champ c4', { invalide: form.errors.commune }]">
        <label for="commune">Commune <span class="requis">*</span></label>
        <input id="commune" v-model="form.commune" list="communes" required maxlength="255">
        <datalist id="communes"><option v-for="c in communes" :key="c" :value="c" /></datalist>
        <span v-if="form.errors.commune" class="erreur">{{ form.errors.commune }}</span>
      </div>
      <div :class="['champ c4', { invalide: form.errors.quartier }]">
        <label for="quartier">Quartier ou village <span class="requis">*</span></label>
        <input id="quartier" v-model="form.quartier" required maxlength="255">
        <span v-if="form.errors.quartier" class="erreur">{{ form.errors.quartier }}</span>
      </div>
      <div :class="['champ c4', { invalide: form.errors.langue_nationale }]">
        <label for="langue">Langue nationale <span class="requis">*</span></label>
        <select id="langue" v-model="form.langue_nationale" required><option value="">— Choisir —</option><option v-for="l in options.langues" :key="l" :value="l">{{ l }}</option></select>
        <span v-if="form.errors.langue_nationale" class="erreur">{{ form.errors.langue_nationale }}</span>
      </div>
      <div :class="['champ c4', { invalide: form.errors.operateur_id }]">
        <label for="op">Opérateur</label>
        <select id="op" v-model="form.operateur_id"><option :value="null">— Aucun —</option><option v-for="o in options.operateurs" :key="o.id" :value="o.id">{{ o.nom_organisation }}</option></select>
        <span v-if="form.errors.operateur_id" class="erreur">{{ form.errors.operateur_id }}</span>
      </div>
      <div :class="['champ c4', { invalide: form.errors.latitude }]">
        <label for="lat">Latitude</label><input id="lat" v-model="form.latitude" type="number" step="0.000001">
        <span v-if="form.errors.latitude" class="erreur">{{ form.errors.latitude }}</span>
      </div>
      <div :class="['champ c4', { invalide: form.errors.longitude }]">
        <label for="lng">Longitude</label><input id="lng" v-model="form.longitude" type="number" step="0.000001">
        <span v-if="form.errors.longitude" class="erreur">{{ form.errors.longitude }}</span>
      </div>
    </div>
    <div class="actions" style="margin-top:1.4rem">
      <button class="btn btn-principal" type="submit" :disabled="form.processing">{{ recodification ? 'Recodifier et enregistrer' : 'Enregistrer' }}</button>
    </div>
  </form>
</template>

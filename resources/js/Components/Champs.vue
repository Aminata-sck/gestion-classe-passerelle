<script setup>
// Formulaire générique : la liste des champs décrit libellé, type, options et largeur.
// Les erreurs viennent de la validation du serveur (useForm d'Inertia).
defineProps({ form: { type: Object, required: true }, champs: { type: Array, required: true } });
</script>

<template>
  <div class="formulaire">
    <div v-for="c in champs" :key="c.nom" :class="['champ', c.col ?? 'c6', { invalide: form.errors[c.nom] }]">
      <label v-if="c.type !== 'case'" :for="'f-' + c.nom">{{ c.label }}<span v-if="c.requis" class="requis"> *</span></label>
      <select v-if="c.type === 'select'" :id="'f-' + c.nom" v-model="form[c.nom]" :required="c.requis">
        <option v-if="!c.sansVide" :value="c.vide ?? ''">{{ c.placeholder ?? '— Choisir —' }}</option>
        <option v-for="o in c.options" :key="o.valeur" :value="o.valeur">{{ o.libelle }}</option>
      </select>
      <textarea v-else-if="c.type === 'textarea'" :id="'f-' + c.nom" v-model="form[c.nom]" :maxlength="c.max"></textarea>
      <label v-else-if="c.type === 'case'" class="case"><input :id="'f-' + c.nom" v-model="form[c.nom]" type="checkbox"> {{ c.label }}</label>
      <input v-else :id="'f-' + c.nom" v-model="form[c.nom]" :type="c.type ?? 'text'" :required="c.requis" :step="c.step" :min="c.min" :max="c.maxValeur" :readonly="c.lecture" :autocomplete="c.autocomplete ?? 'off'">
      <span v-if="c.aide" class="aide">{{ c.aide }}</span>
      <span v-if="form.errors[c.nom]" class="erreur">{{ form.errors[c.nom] }}</span>
    </div>
  </div>
</template>

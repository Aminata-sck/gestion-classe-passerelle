<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Fenetre from './Fenetre.vue';

// Demande de confirmation avant une action irréversible, envoyée en DELETE ou POST
// (jamais par un simple lien), avec le jeton CSRF ajouté par Inertia.
const props = defineProps({ titre: { type: String, default: 'Confirmer' }, message: String, url: String, methode: { type: String, default: 'delete' }, libelle: { type: String, default: 'Supprimer' } });
const emit = defineEmits(['fermer']);
const envoi = ref(false);
const confirmer = () => {
  envoi.value = true;
  router.visit(props.url, { method: props.methode, preserveScroll: true, onFinish: () => { envoi.value = false; emit('fermer'); } });
};
</script>

<template>
  <Fenetre :titre="titre" taille="petite" @fermer="emit('fermer')">
    <p style="margin:0">{{ message }}</p>
    <template #pied>
      <button type="button" class="btn" @click="emit('fermer')">Annuler</button>
      <button type="button" class="btn btn-danger" :disabled="envoi" @click="confirmer">{{ libelle }}</button>
    </template>
  </Fenetre>
</template>

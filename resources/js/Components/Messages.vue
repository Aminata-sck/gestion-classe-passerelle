<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const masques = ref({});
const messages = computed(() => ['success', 'warning', 'error']
  .map((type) => ({ type, texte: page.props.flash?.[type] }))
  .filter((m) => m.texte && !masques.value[m.type + m.texte]));
watch(() => page.props.flash, () => { masques.value = {}; });
</script>

<template>
  <div v-for="m in messages" :key="m.type + m.texte" :class="['message', 'message-' + m.type]" role="status">
    <span>{{ m.texte }}</span>
    <button type="button" aria-label="Fermer" @click="masques[m.type + m.texte] = true">&times;</button>
  </div>
</template>

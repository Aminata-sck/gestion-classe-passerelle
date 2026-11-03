<script setup>
import { onBeforeUnmount, onMounted } from 'vue';

defineProps({ titre: String, taille: { type: String, default: '' } });
const emit = defineEmits(['fermer']);
const echap = (e) => { if (e.key === 'Escape') emit('fermer'); };
onMounted(() => document.addEventListener('keydown', echap));
onBeforeUnmount(() => document.removeEventListener('keydown', echap));
</script>

<template>
  <Teleport to="body">
    <div class="fond" @mousedown.self="emit('fermer')">
      <div :class="['fenetre', taille]" role="dialog" aria-modal="true" :aria-label="titre">
        <div class="fenetre-tete">
          <h2>{{ titre }}</h2>
          <button type="button" aria-label="Fermer" @click="emit('fermer')">&times;</button>
        </div>
        <div class="fenetre-corps"><slot /></div>
        <div v-if="$slots.pied" class="fenetre-pied"><slot name="pied" /></div>
      </div>
    </div>
  </Teleport>
</template>

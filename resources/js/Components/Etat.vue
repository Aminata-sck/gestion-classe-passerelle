<script setup>
import { etats } from '../lib/format';
defineProps({ etat: String });
</script>

<template>
  <span :class="['badge', 'badge-' + (etats[etat]?.ton ?? 'muted')]">{{ etats[etat]?.libelle ?? etat }}</span>
</template>

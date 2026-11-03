<script setup>
defineProps({ titre: String, texte: String, fil: String });
</script>

<template>
  <header class="entete">
    <div>
      <div v-if="fil" class="fil">{{ fil }}</div>
      <h1>{{ titre }}</h1>
      <p v-if="texte">{{ texte }}</p>
    </div>
    <div class="actions"><slot /></div>
  </header>
</template>

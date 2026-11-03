<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ meta: { type: Object, required: true } });
const lien = (n) => {
  const url = new URL(window.location.href);
  url.searchParams.set('page', n);
  return url.pathname + url.search;
};
const pages = computed(() => {
  const { page, pages } = props.meta;
  const debut = Math.max(1, Math.min(page - 2, pages - 4));
  return Array.from({ length: Math.min(5, pages) }, (_, i) => debut + i).filter((n) => n >= 1 && n <= pages);
});
</script>

<template>
  <div v-if="meta.total" class="pagination">
    <span>{{ meta.from }} à {{ meta.to }} sur {{ meta.total }}</span>
    <nav v-if="meta.pages > 1" class="pages" aria-label="Pagination">
      <Link :href="lien(meta.page - 1)" :class="{ inactif: meta.page <= 1 }" preserve-scroll>‹</Link>
      <Link v-for="n in pages" :key="n" :href="lien(n)" :class="{ courante: n === meta.page }" preserve-scroll>{{ n }}</Link>
      <Link :href="lien(meta.page + 1)" :class="{ inactif: meta.page >= meta.pages }" preserve-scroll>›</Link>
    </nav>
  </div>
</template>

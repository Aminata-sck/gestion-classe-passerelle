import { router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

/** Filtres de liste synchronisés avec l'adresse (recherche différée de 300 ms). */
export function useFiltres(initiaux, url) {
  const filtres = reactive({ ...initiaux });
  let minuteur;
  watch(filtres, () => {
    clearTimeout(minuteur);
    minuteur = setTimeout(() => {
      const params = Object.fromEntries(Object.entries(filtres).filter(([, v]) => v !== '' && v !== null && v !== undefined));
      router.get(url, params, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
  });
  return filtres;
}

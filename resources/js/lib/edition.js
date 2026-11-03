import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

/** Fenêtre d'ajout ou de modification : POST sur l'URL de base, PUT sur base/id. */
export function useEdition(vierge, base) {
  const form = useForm({ ...vierge });
  const ouverte = ref(false);
  const id = ref(null);
  const ouvrir = (ligne = null) => {
    form.clearErrors();
    id.value = ligne?.id ?? null;
    Object.keys(vierge).forEach((k) => { form[k] = ligne ? (ligne[k] ?? vierge[k]) : vierge[k]; });
    ouverte.value = true;
  };
  const fermer = () => { ouverte.value = false; };
  const envoyer = () => {
    const options = { preserveScroll: true, onSuccess: fermer };
    return id.value ? form.put(`${base}/${id.value}`, options) : form.post(base, options);
  };
  return { form, ouverte, id, ouvrir, fermer, envoyer };
}

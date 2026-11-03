<script setup>
import { Head, Link } from '@inertiajs/vue3';
const props = defineProps({ statut: Number });
const textes = {
  403: ['Accès refusé', "Votre profil ne permet pas d'effectuer cette action."],
  404: ['Introuvable', 'Cet élément est introuvable ou hors de votre périmètre.'],
  500: ['Erreur du serveur', 'Une erreur est survenue. Elle a été enregistrée pour l’administrateur.'],
  503: ['Maintenance', 'La plateforme est momentanément indisponible.'],
};
const [titre, texte] = textes[props.statut] ?? ['Erreur', 'Une erreur est survenue.'];
</script>

<template>
  <Head :title="titre" />
  <div class="carte" style="max-width:640px;margin:4rem auto;text-align:center;padding:2.5rem">
    <div style="font-size:3rem;font-weight:700;color:var(--laterite)">{{ statut }}</div>
    <h1 style="font-size:1.5rem;margin:.4rem 0">{{ titre }}</h1>
    <p class="secondaire">{{ texte }}</p>
    <Link href="/dashboard" class="btn btn-principal" style="margin-top:1rem">Retour au tableau de bord</Link>
  </div>
</template>

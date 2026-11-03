<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import EnTete from '../../Components/EnTete.vue';
import Champs from '../../Components/Champs.vue';

const props = defineProps({ obligatoire: Boolean });
const form = useForm({ current_password: '', password: '', password_confirmation: '' });
const champs = [
  { nom: 'current_password', label: props.obligatoire ? 'Mot de passe temporaire' : 'Mot de passe actuel', type: 'password', requis: true, col: 'c12', autocomplete: 'current-password' },
  { nom: 'password', label: 'Nouveau mot de passe', type: 'password', requis: true, col: 'c6', autocomplete: 'new-password', aide: '12 caractères au moins, avec des lettres et des chiffres.' },
  { nom: 'password_confirmation', label: 'Confirmation', type: 'password', requis: true, col: 'c6', autocomplete: 'new-password' },
];
const envoyer = () => form.put('/password', { onFinish: () => form.reset() });
</script>

<template>
  <Head title="Mot de passe" />
  <EnTete titre="Changer le mot de passe" :texte="obligatoire ? 'Première connexion : remplacez le mot de passe temporaire reçu par e-mail.' : 'Choisissez un mot de passe que vous n’utilisez nulle part ailleurs.'" />
  <form class="carte" style="max-width:720px" @submit.prevent="envoyer">
    <Champs :form="form" :champs="champs" />
    <div class="actions" style="margin-top:1.2rem"><button class="btn btn-principal" :disabled="form.processing">Enregistrer</button></div>
  </form>
</template>

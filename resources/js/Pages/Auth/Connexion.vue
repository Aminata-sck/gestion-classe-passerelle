<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '../../Layouts/AuthLayout.vue';

defineOptions({ layout: AuthLayout });
const page = usePage();
const form = useForm({ email: '', password: '' });
const envoyer = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
  <Head title="Connexion" />
  <h2>Connexion</h2>
  <p>Accédez à l'espace de votre structure, de votre inspection ou du niveau central.</p>
  <div v-if="page.props.flash?.error" class="message message-error">{{ page.props.flash.error }}</div>
  <form @submit.prevent="envoyer" novalidate>
    <div :class="['champ', { invalide: form.errors.email }]">
      <label for="email">Adresse e-mail</label>
      <input id="email" v-model="form.email" type="email" autocomplete="username" required autofocus>
      <span v-if="form.errors.email" class="erreur" role="alert">{{ form.errors.email }}</span>
    </div>
    <div :class="['champ', { invalide: form.errors.password }]">
      <label for="password">Mot de passe</label>
      <input id="password" v-model="form.password" type="password" autocomplete="current-password" required>
      <span v-if="form.errors.password" class="erreur">{{ form.errors.password }}</span>
    </div>
    <button class="btn btn-principal" type="submit" :disabled="form.processing">Se connecter</button>
    <p class="secondaire" style="margin:0">Après cinq mots de passe erronés, la connexion est suspendue pendant 15 minutes.</p>
  </form>
</template>

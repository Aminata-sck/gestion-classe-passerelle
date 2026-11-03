<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Icone from '../Components/Icone.vue';
import Messages from '../Components/Messages.vue';
import { initiales } from '../lib/format';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const menu = computed(() => page.props.menu ?? []);
const chemin = computed(() => page.url.split('?')[0]);
const ouvert = ref(false); // menu latéral sur téléphone
const groupes = ref({});

const actif = (url) => chemin.value === url || (url !== '/dashboard' && chemin.value.startsWith(url + '/'));
const groupeActif = (m) => m.items.some((i) => actif(i.url));
const estOuvert = (m) => groupes.value[m.id] ?? groupeActif(m);
const basculer = (m) => { groupes.value[m.id] = !estOuvert(m); };
const icone = (m) => ({ 1: 'tableau', 2: 'facilitateur', 3: 'superviseur', 4: 'operateur', 5: 'apprenant', 6: 'administration', 7: 'classe', 8: 'structure', 12: 'configuration' }[m.id] ?? 'chevron');
const deconnexion = () => router.post('/logout');
watch(chemin, () => { ouvert.value = false; });
</script>

<template>
  <div class="cadre">
    <div class="marque">
      <svg width="28" height="20" viewBox="0 0 28 20" aria-hidden="true"><path d="M1 19 9 7l5 7 4-5 9 10z" fill="#d0842a"/></svg>
      Classes passerelles
    </div>
    <header class="barre">
      <button class="bouton-menu" type="button" aria-label="Menu" @click="ouvert = !ouvert"><Icone nom="menu" :taille="22" /></button>
      <span class="titre-page">Ministère de l'Éducation nationale — DALN</span>
      <div class="barre-droite">
        <Link href="/password" class="btn btn-sm btn-clair"><Icone nom="cadenas" :taille="15" /> Mot de passe</Link>
      </div>
    </header>

    <aside :class="['lateral', { ouvert }]" aria-label="Navigation">
      <div class="profil">
        <div class="avatar">{{ initiales(user?.nom) }}</div>
        <div>
          <strong>{{ user?.nom }}</strong>
          <small>{{ user?.profil }} · {{ user?.perimetre }}</small>
        </div>
      </div>
      <nav class="nav">
        <template v-for="m in menu" :key="m.id">
          <Link v-if="m.items.length === 1" :href="m.items[0].url" :class="{ actif: actif(m.items[0].url) }">
            <Icone :nom="icone(m)" /> {{ m.label }}
          </Link>
          <div v-else :class="['nav-groupe', { ouvert: estOuvert(m) }]">
            <button type="button" :aria-expanded="estOuvert(m)" @click="basculer(m)">
              <Icone :nom="icone(m)" /> {{ m.label }} <Icone class="chevron" nom="chevron" :taille="14" />
            </button>
            <div v-show="estOuvert(m)" class="nav-sous">
              <Link v-for="i in m.items" :key="i.id" :href="i.url" :class="{ actif: actif(i.url) }">{{ i.label }}</Link>
            </div>
          </div>
        </template>
      </nav>
      <div class="deconnexion nav">
        <a href="#" @click.prevent="deconnexion"><Icone nom="sortie" /> Déconnexion</a>
      </div>
    </aside>

    <main class="contenu">
      <div class="contenu-inner">
        <Messages />
        <slot />
      </div>
    </main>
  </div>
</template>

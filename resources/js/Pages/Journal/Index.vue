<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import EnTete from '../../Components/EnTete.vue';
import Fenetre from '../../Components/Fenetre.vue';
import Pagination from '../../Components/Pagination.vue';
import { useFiltres } from '../../lib/filtres';

const props = defineProps({ entrees: Object, actions: Array, filtres: Object });
const filtres = useFiltres({ action: props.filtres.action ?? '', utilisateur: props.filtres.utilisateur ?? '', du: props.filtres.du ?? '' }, '/journal');
const detail = ref(null);
const ton = (a) => (a.includes('REFUSE') || a.includes('ECHEC') || a.includes('BLOQUEE') ? 'badge-danger' : a === 'SUPPRESSION' ? 'badge-warning' : a.startsWith('CONNEXION') || a === 'DECONNEXION' ? 'badge-muted' : 'badge-success');
const json = (v) => (v ? JSON.stringify(v, null, 2) : '—');
</script>

<template>
  <Head title="Journal d'audit" />
  <EnTete fil="Administration" titre="Journal d'audit" texte="Historique des connexions, des modifications et des refus d'accès. Il ne peut être ni modifié ni supprimé depuis la plateforme." />
  <section class="carte">
    <div class="filtres">
      <label class="filtre">Action<select v-model="filtres.action"><option value="">Toutes</option><option v-for="a in actions" :key="a">{{ a }}</option></select></label>
      <label class="filtre large">Utilisateur<input v-model="filtres.utilisateur" type="search" placeholder="E-mail"></label>
      <label class="filtre">Depuis le<input v-model="filtres.du" type="date"></label>
    </div>
    <div class="tableau-conteneur">
      <table class="tableau">
        <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Objet</th><th>Adresse IP</th><th></th></tr></thead>
        <tbody>
          <tr v-for="e in entrees.data" :key="e.id">
            <td style="white-space:nowrap">{{ e.date }}</td><td>{{ e.utilisateur }}</td>
            <td><span :class="['badge', ton(e.action)]">{{ e.action }}</span></td>
            <td>{{ e.objet || '—' }}</td><td><code>{{ e.ip }}</code></td>
            <td class="acts"><button v-if="e.avant || e.apres" class="btn btn-sm" @click="detail = e">Détail</button></td>
          </tr>
          <tr v-if="!entrees.data.length"><td colspan="6" class="vide">Aucune entrée.</td></tr>
        </tbody>
      </table>
    </div>
    <Pagination :meta="entrees.meta" />
  </section>
  <Fenetre v-if="detail" :titre="`${detail.action} — ${detail.objet}`" @fermer="detail = null">
    <div class="grille g2">
      <div><h3 style="font-size:.95rem;margin-bottom:.4rem">Avant</h3><pre style="background:var(--page);padding:.8rem;border-radius:8px;overflow:auto;font-size:.82rem">{{ json(detail.avant) }}</pre></div>
      <div><h3 style="font-size:.95rem;margin-bottom:.4rem">Après</h3><pre style="background:var(--page);padding:.8rem;border-radius:8px;overflow:auto;font-size:.82rem">{{ json(detail.apres) }}</pre></div>
    </div>
  </Fenetre>
</template>

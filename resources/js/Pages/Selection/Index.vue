<script setup>
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import EnTete from '../../Components/EnTete.vue';

const props = defineProps({ structures: Array, criteres: Object, resultat: Object });
const c = reactive({ structure_id: props.criteres.structure_id ?? '', places: props.criteres.places, date: props.criteres.date });
const calculer = () => router.get('/selection', c, { preserveScroll: true });
const age = (l) => (l.age_ans === null ? '—' : `${l.age_ans} ans`);
</script>

<template>
  <Head title="Classement de sélection" />
  <EnTete titre="Classement de sélection" texte="Éligibilité : 8 à 14 ans, déscolarisé ou non scolarisé. Départage : vulnérabilité, puis filles, puis proximité de 12 ans." />
  <form class="carte filtres" style="margin-bottom:1.25rem" @submit.prevent="calculer">
    <label class="filtre large">Structure
      <select v-model="c.structure_id" required><option value="">— Choisir une structure —</option><option v-for="s in structures" :key="s.id" :value="s.id">{{ s.nom_structure }} ({{ s.code_structure }})</option></select>
    </label>
    <label class="filtre">Nombre de places<input v-model.number="c.places" type="number" min="1" max="200" required></label>
    <label class="filtre">Date de référence<input v-model="c.date" type="date" required></label>
    <button class="btn btn-principal">Établir le classement</button>
  </form>

  <template v-if="resultat">
    <div class="grille g3" style="margin-bottom:1.25rem">
      <div class="chiffre"><strong>{{ resultat.retenus.length }}</strong>retenus</div>
      <div class="chiffre"><strong>{{ resultat.attente.length }}</strong>en liste d'attente</div>
      <div class="chiffre"><strong>{{ resultat.ecartes.length }}</strong>écartés</div>
    </div>
    <div class="grille g2">
      <section class="carte">
        <h2>Retenus</h2>
        <p class="sous-titre">Dans l'ordre de priorité, dans la limite des places.</p>
        <table class="tableau">
          <thead><tr><th>Rang</th><th>Enfant</th><th>Sexe</th><th>Âge</th><th>Vulnérabilité</th></tr></thead>
          <tbody>
            <tr v-for="l in resultat.retenus" :key="l.id"><td><span class="rang">{{ l.rang }}</span></td><td><strong>{{ l.nom }}</strong><div class="secondaire">{{ l.situation }}</div></td><td>{{ l.sexe === 'F' ? 'Fille' : 'Garçon' }}</td><td>{{ age(l) }}</td><td><span v-if="l.vulnerable" class="badge badge-warning">oui</span><span v-else class="secondaire">non</span></td></tr>
            <tr v-if="!resultat.retenus.length"><td colspan="5" class="vide">Aucun enfant éligible.</td></tr>
          </tbody>
        </table>
      </section>
      <div>
        <section class="carte">
          <h2>Liste d'attente</h2>
          <p class="sous-titre">Éligibles au-delà du nombre de places.</p>
          <table class="tableau">
            <thead><tr><th>Rang</th><th>Enfant</th><th>Sexe</th><th>Âge</th><th>Vulnérabilité</th></tr></thead>
            <tbody>
              <tr v-for="l in resultat.attente" :key="l.id"><td><span class="rang attente">{{ l.rang }}</span></td><td>{{ l.nom }}</td><td>{{ l.sexe === 'F' ? 'Fille' : 'Garçon' }}</td><td>{{ age(l) }}</td><td>{{ l.vulnerable ? 'oui' : 'non' }}</td></tr>
              <tr v-if="!resultat.attente.length"><td colspan="5" class="vide">Personne en attente.</td></tr>
            </tbody>
          </table>
        </section>
        <section class="carte">
          <h2>Écartés</h2>
          <p class="sous-titre">Enfants qui ne remplissent pas les critères d'éligibilité.</p>
          <table class="tableau">
            <thead><tr><th>Enfant</th><th>Motif</th></tr></thead>
            <tbody>
              <tr v-for="l in resultat.ecartes" :key="l.id"><td>{{ l.nom }}</td><td><span class="badge badge-danger">{{ l.motif }}</span></td></tr>
              <tr v-if="!resultat.ecartes.length"><td colspan="2" class="vide">Aucun enfant écarté.</td></tr>
            </tbody>
          </table>
        </section>
      </div>
    </div>
  </template>
  <div v-else class="carte vide">Choisissez une structure et le nombre de places pour établir le classement.</div>
</template>

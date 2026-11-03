<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Etat from '../../Components/Etat.vue';
import { dateLongue, nombre, note, peut } from '../../lib/format';

const props = defineProps({ chiffres: Object, parcours: Array, parEtat: Array, parNiveau: Array, dernieres: Array });
const page = usePage();
const user = computed(() => page.props.auth.user);
const descriptions = [
  'enfants hors école saisis dans la plateforme',
  'âgés de 8 à 14 ans, déscolarisés ou non scolarisés',
  "suivent les cours d'une classe passerelle",
  'ont au moins une note enregistrée',
  "suivi de l'insertion dans le formel : module à venir",
];
const total = computed(() => props.parcours[0].valeur || 0);
const pct = (v) => (total.value ? Math.round((v / total.value) * 100) : 0);
const maxEtat = computed(() => Math.max(1, ...props.parEtat.map((e) => e.total)));
const maxNiveau = computed(() => Math.max(1, ...props.parNiveau.map((n) => n.total)));
const raccourcis = computed(() => [
  ['Structures', '/structures', [8, 15]], ['Classes', '/classes', [7, 14]], ['Apprenants', '/apprenant', [5, 13]],
  ['Classement de sélection', '/selection', [5, 40]], ["Journal d'audit", '/journal', [6, 41]],
].filter(([, , d]) => peut(page, d[0], d[1], 1)));
</script>

<template>
  <Head title="Tableau de bord" />
  <header class="entete">
    <div>
      <h1>Bonjour, {{ user.nom }}</h1>
      <p style="text-transform:none">{{ dateLongue() }} — périmètre : <strong>{{ user.perimetre }}</strong></p>
    </div>
    <div class="actions">
      <Link v-for="[l, u] in raccourcis" :key="u" :href="u" class="pastille-lien">{{ l }}</Link>
    </div>
  </header>

  <section class="carte sombre" style="padding:1.6rem 1.8rem">
    <h2 style="font-size:1.25rem">Le parcours des enfants</h2>
    <p class="sous-titre" style="margin:.3rem 0 0">Du recensement jusqu'au retour dans le système formel.</p>
    <div class="parcours">
      <div v-for="(e, i) in parcours" :key="e.etape" :class="['etape', { avenir: e.valeur === null }]">
        <span class="rond">{{ i + 1 }}</span>
        <strong>{{ e.valeur === null ? '—' : nombre(e.valeur) }}</strong>
        <span class="lib">{{ e.etape }}</span>
        <span class="desc">{{ descriptions[i] }}</span>
        <template v-if="e.valeur !== null">
          <div class="jauge"><span :style="{ width: pct(e.valeur) + '%' }"></span></div>
          <small>{{ pct(e.valeur) }} %</small>
        </template>
      </div>
    </div>
  </section>

  <div class="grille g3">
    <section class="carte">
      <h2>Filles et garçons</h2>
      <p class="sous-titre">Les filles sont prioritaires à la sélection.</p>
      <div class="repartition" role="img" :aria-label="`${chiffres.filles} filles sur ${chiffres.apprenants} apprenants`">
        <span class="f" :style="{ width: (chiffres.apprenants ? chiffres.filles / chiffres.apprenants * 100 : 0) + '%' }"></span>
        <span class="g" :style="{ width: (chiffres.apprenants ? (chiffres.apprenants - chiffres.filles) / chiffres.apprenants * 100 : 0) + '%' }"></span>
      </div>
      <p style="margin:.6rem 0 1rem;font-size:.92rem">
        <span style="color:var(--laterite)">■</span> Filles <strong>{{ chiffres.filles }}</strong>
        ({{ chiffres.apprenants ? Math.round(chiffres.filles / chiffres.apprenants * 100) : 0 }} %)
        &nbsp; <span style="color:#168a6e">■</span> Garçons <strong>{{ chiffres.apprenants - chiffres.filles }}</strong>
      </p>
      <div class="encadre" style="display:flex;gap:.8rem;align-items:center">
        <strong style="font-size:1.6rem">{{ chiffres.vulnerables }}</strong>
        <span>enfants en situation de vulnérabilité (handicap, orphelin, parents divorcés, sous tutorat)</span>
      </div>
    </section>

    <section class="carte">
      <h2>{{ chiffres.structures }} structure{{ chiffres.structures > 1 ? 's' : '' }}</h2>
      <p class="sous-titre">Par état de fonctionnement</p>
      <div class="barres">
        <div v-for="e in parEtat" :key="e.etat" class="barre-ligne">
          <span>{{ e.libelle }}</span>
          <span class="barre-fond"><span :style="{ width: (e.total / maxEtat * 100) + '%' }"></span></span>
          <strong style="text-align:right">{{ e.total }}</strong>
        </div>
      </div>
    </section>

    <section class="carte">
      <h2>{{ chiffres.classes }} classes, {{ chiffres.facilitateurs }} facilitateurs</h2>
      <p class="sous-titre">Classes par niveau — moyenne générale : <strong>{{ note(chiffres.moyenne) }}</strong> / 20</p>
      <div class="colonnes">
        <div v-for="n in parNiveau" :key="n.niveau" class="colonne">
          {{ n.total }}<span class="b" :style="{ height: (n.total / maxNiveau * 100) + '%' }"></span>
        </div>
      </div>
      <div class="colonnes-legende"><span v-for="n in parNiveau" :key="n.niveau">{{ n.niveau }}</span></div>
    </section>
  </div>

  <section class="carte">
    <h2>Dernières structures enregistrées</h2>
    <p class="sous-titre">Dans votre périmètre</p>
    <div class="tableau-conteneur">
      <table class="tableau">
        <thead><tr><th>Code</th><th>Structure</th><th>IEF</th><th>État</th></tr></thead>
        <tbody>
          <tr v-for="s in dernieres" :key="s.id">
            <td><code>{{ s.code }}</code></td>
            <td><Link :href="`/structures/${s.id}`">{{ s.nom }}</Link></td>
            <td>{{ s.ief }}</td>
            <td><Etat :etat="s.etat" /></td>
          </tr>
          <tr v-if="!dernieres.length"><td colspan="4" class="vide">Aucune structure dans votre périmètre.</td></tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

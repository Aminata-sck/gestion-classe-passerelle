<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Confirmation from '../../Components/Confirmation.vue';
import EnTete from '../../Components/EnTete.vue';
import Etat from '../../Components/Etat.vue';
import Icone from '../../Components/Icone.vue';

const props = defineProps({ structure: Object, droits: Object });
const archiver = ref(false);
const s = props.structure;
</script>

<template>
  <Head :title="s.nom" />
  <EnTete fil="Structures" :titre="s.nom" :texte="`Code ${s.code} · ${s.type ?? 'type non renseigné'}`">
    <Link href="/structures" class="btn"><Icone nom="retour" :taille="15" /> Liste</Link>
    <Link v-if="droits.modifier && !s.archivee" :href="`/structures/${s.id}/edit`" class="btn btn-principal"><Icone nom="crayon" :taille="15" /> Modifier</Link>
    <button v-if="droits.supprimer && !s.archivee" class="btn btn-lien-danger" @click="archiver = true">Archiver</button>
  </EnTete>

  <div v-if="s.archivee" class="encadre" style="margin-bottom:1.2rem">
    Cette structure est archivée.<template v-if="s.remplacee_par"> Elle a été recodifiée : nouvelle structure
      <Link :href="`/structures/${s.remplacee_par.id}`"><code>{{ s.remplacee_par.code_structure }}</code></Link>.</template>
  </div>
  <div v-if="s.ancienne" class="encadre encadre-info" style="margin-bottom:1.2rem">
    Issue de la recodification de la structure <Link :href="`/structures/${s.ancienne.id}`"><code>{{ s.ancienne.code }}</code></Link>, archivée.
  </div>

  <div class="grille g2">
    <section class="carte">
      <h2>Identification</h2>
      <p class="sous-titre">Localisation et rattachement territorial</p>
      <dl class="details">
        <div><dt>Code</dt><dd><code>{{ s.code }}</code></dd></div>
        <div><dt>État</dt><dd><Etat :etat="s.etat" /></dd></div>
        <div><dt>Langue nationale</dt><dd>{{ s.langue }}</dd></div>
        <div><dt>Région</dt><dd>{{ s.region }}</dd></div>
        <div><dt>Département</dt><dd>{{ s.departement }}</dd></div>
        <div><dt>Commune</dt><dd>{{ s.commune }}</dd></div>
        <div><dt>Quartier</dt><dd>{{ s.quartier }}</dd></div>
        <div><dt>IA</dt><dd>{{ s.ia }}</dd></div>
        <div><dt>IEF</dt><dd>{{ s.ief }}</dd></div>
        <div><dt>Opérateur</dt><dd>{{ s.operateur ?? '—' }}</dd></div>
        <div><dt>Coordonnées</dt><dd>{{ s.latitude && s.longitude ? `${Number(s.latitude).toFixed(4)}, ${Number(s.longitude).toFixed(4)}` : '—' }}</dd></div>
        <div><dt>Apprenants</dt><dd>{{ s.apprenants }}</dd></div>
      </dl>
    </section>
    <section class="carte">
      <h2>Classes ({{ s.classes.length }})</h2>
      <p class="sous-titre">Chaque classe garde son code, même si elle change de nom.</p>
      <table class="tableau">
        <thead><tr><th>Code</th><th>Classe</th><th>Niveau</th><th>Facilitateur</th></tr></thead>
        <tbody>
          <tr v-for="c in s.classes" :key="c.id"><td><code>{{ c.code }}</code></td><td>{{ c.nom }}</td><td>{{ c.niveau }}</td><td>{{ c.facilitateur ?? '—' }}</td></tr>
          <tr v-if="!s.classes.length"><td colspan="4" class="vide">Aucune classe.</td></tr>
        </tbody>
      </table>
      <div class="actions" style="margin-top:1rem"><Link :href="`/selection?structure_id=${s.id}`" class="btn btn-sm">Classement de sélection</Link></div>
    </section>
  </div>

  <Confirmation v-if="archiver" titre="Archiver la structure" :url="`/structures/${s.id}`" libelle="Archiver"
    :message="`La structure sera archivée ; son code ${s.code} restera consultable.`" @fermer="archiver = false" />
</template>

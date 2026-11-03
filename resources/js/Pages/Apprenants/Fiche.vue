<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Champs from '../../Components/Champs.vue';
import Confirmation from '../../Components/Confirmation.vue';
import EnTete from '../../Components/EnTete.vue';
import Fenetre from '../../Components/Fenetre.vue';
import Icone from '../../Components/Icone.vue';
import { champsApprenant, viergeApprenant } from '../../lib/apprenant';
import { useEdition } from '../../lib/edition';
import { note } from '../../lib/format';

const props = defineProps({ apprenant: Object, evaluations: Array, options: Object, droits: Object });
const a = computed(() => props.apprenant);
const { form, ouverte, ouvrir, fermer, envoyer } = useEdition(viergeApprenant, '/apprenant');
const champs = computed(() => champsApprenant(props.options, form));
const evaluation = useForm({ libelle: '', note1: '', note2: '', composition: '' });
const apercu = computed(() => {
  const [n1, n2, c] = [evaluation.note1, evaluation.note2, evaluation.composition].map((v) => parseFloat(String(v).replace(',', '.')));
  return [n1, n2, c].every((v) => !Number.isNaN(v)) ? Math.round((((n1 + n2) / 2 + c) / 2) * 100) / 100 : null;
});
const enregistrerNote = () => evaluation.post(`/apprenant/${a.value.id}/evaluations`, { preserveScroll: true, onSuccess: () => evaluation.reset() });
const aSupprimer = ref(null);
const supprimerDossier = ref(false);
const champsNote = [
  { nom: 'libelle', label: 'Évaluation', requis: true, col: 'c12' },
  { nom: 'note1', label: 'Devoir 1', type: 'number', step: '0.25', min: 0, maxValeur: 20, requis: true, col: 'c4' },
  { nom: 'note2', label: 'Devoir 2', type: 'number', step: '0.25', min: 0, maxValeur: 20, requis: true, col: 'c4' },
  { nom: 'composition', label: 'Composition', type: 'number', step: '0.25', min: 0, maxValeur: 20, requis: true, col: 'c4' },
];
</script>

<template>
  <Head :title="`${a.prenom} ${a.nom}`" />
  <EnTete fil="Apprenants" :titre="`${a.prenom} ${a.nom}`" :texte="`${a.sexe === 'F' ? 'Fille' : 'Garçon'}, ${a.age ?? '?'} ans · ${a.situation_libelle} · ${a.structure ?? 'sans structure'}`">
    <Link href="/apprenant" class="btn"><Icone nom="retour" :taille="15" /> Liste</Link>
    <button v-if="droits.modifier" class="btn btn-principal" @click="ouvrir(a)"><Icone nom="crayon" :taille="15" /> Modifier le dossier</button>
    <button v-if="droits.supprimer" class="btn btn-lien-danger" @click="supprimerDossier = true">Supprimer</button>
  </EnTete>

  <div class="grille g2">
    <section class="carte">
      <h2>Dossier</h2>
      <p class="sous-titre">Identité, situation et famille <span v-if="a.vulnerable" class="badge badge-warning">en situation de vulnérabilité</span></p>
      <dl class="details">
        <div><dt>Date de naissance</dt><dd>{{ a.date_naissance ? new Date(a.date_naissance).toLocaleDateString('fr-FR') : '—' }}</dd></div>
        <div><dt>Lieu de naissance</dt><dd>{{ a.lieu_naissance || '—' }}</dd></div>
        <div><dt>Dernière classe</dt><dd>{{ a.derniere_classe || '—' }}</dd></div>
        <div><dt>Handicap</dt><dd>{{ a.handicap === 'oui' ? 'Oui' : 'Non' }}</dd></div>
        <div><dt>Situation familiale</dt><dd>{{ a.situation_familiale || '—' }}</dd></div>
        <div><dt>Commune</dt><dd>{{ a.commune || '—' }}</dd></div>
        <div><dt>Parent ou tuteur</dt><dd>{{ a.nom_parent || '—' }}</dd></div>
        <div><dt>Téléphone</dt><dd>{{ a.telephone_parent || '—' }}</dd></div>
        <div><dt>Classe</dt><dd>{{ a.classe || 'Pas encore affecté' }}</dd></div>
      </dl>
      <p v-if="a.cause_descolarisation" style="margin:1rem 0 0"><span class="secondaire">Cause de la déscolarisation :</span> {{ a.cause_descolarisation }}</p>
    </section>

    <section class="carte">
      <h2>Saisir une évaluation</h2>
      <p class="sous-titre">Moyenne = (moyenne des deux devoirs + composition) / 2, sur 20.</p>
      <form v-if="droits.modifier" @submit.prevent="enregistrerNote">
        <Champs :form="evaluation" :champs="champsNote" />
        <div class="actions" style="margin-top:1rem;justify-content:space-between">
          <span>Moyenne calculée : <strong style="font-size:1.2rem">{{ apercu === null ? '—' : note(apercu) }}</strong></span>
          <button class="btn btn-principal" :disabled="evaluation.processing">Enregistrer la note</button>
        </div>
      </form>
      <p v-else class="secondaire">Votre profil ne permet pas de saisir des notes.</p>
    </section>
  </div>

  <section class="carte">
    <h2>Évaluations ({{ evaluations.length }})</h2>
    <p class="sous-titre">La moyenne est recalculée par le serveur à l'enregistrement.</p>
    <table class="tableau">
      <thead><tr><th>Évaluation</th><th class="num">Devoir 1</th><th class="num">Devoir 2</th><th class="num">Composition</th><th class="num">Moyenne</th><th>Saisie</th><th></th></tr></thead>
      <tbody>
        <tr v-for="e in evaluations" :key="e.id">
          <td>{{ e.libelle }}</td><td class="num">{{ note(e.note1) }}</td><td class="num">{{ note(e.note2) }}</td><td class="num">{{ note(e.composition) }}</td>
          <td class="num"><strong>{{ note(e.moyenne) }}</strong></td><td class="secondaire">{{ e.date }}<template v-if="e.auteur"> · {{ e.auteur }}</template></td>
          <td class="acts"><button v-if="droits.modifier" class="btn btn-sm btn-lien-danger" aria-label="Supprimer" @click="aSupprimer = e"><Icone nom="corbeille" :taille="15" /></button></td>
        </tr>
        <tr v-if="!evaluations.length"><td colspan="7" class="vide">Aucune évaluation enregistrée.</td></tr>
      </tbody>
    </table>
  </section>

  <Fenetre v-if="ouverte" titre="Modifier le dossier" taille="large" @fermer="fermer">
    <form id="f-app" @submit.prevent="envoyer"><Champs :form="form" :champs="champs" /></form>
    <template #pied><button class="btn" @click="fermer">Annuler</button><button class="btn btn-principal" form="f-app" :disabled="form.processing">Enregistrer</button></template>
  </Fenetre>
  <Confirmation v-if="aSupprimer" titre="Supprimer l'évaluation" :url="`/apprenant/${a.id}/evaluations/${aSupprimer.id}`" :message="`Supprimer « ${aSupprimer.libelle} » ?`" @fermer="aSupprimer = null" />
  <Confirmation v-if="supprimerDossier" titre="Supprimer le dossier" :url="`/apprenant/${a.id}`" :message="`Le dossier de ${a.prenom} ${a.nom} et ses évaluations seront supprimés.`" @fermer="supprimerDossier = false" />
</template>

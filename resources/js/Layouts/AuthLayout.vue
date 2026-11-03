<template>
  <div class="auth">
    <section class="auth-visuel">
      <div style="display:flex;gap:.6rem;align-items:center;font-weight:700">
        <svg width="30" height="22" viewBox="0 0 28 20" aria-hidden="true"><path d="M1 19 9 7l5 7 4-5 9 10z" fill="#d0842a"/></svg>
        Classes passerelles
      </div>
      <div>
        <h1>Ramener chaque enfant vers l'école.</h1>
        <p>Plateforme de gestion des classes passerelles du ministère de l'Éducation nationale, pilotée par la Direction de l'Alphabétisation et des Langues nationales.</p>
      </div>
      <p style="font-size:.85rem;margin:0">MEN — DALN · République du Sénégal</p>
      <svg class="collines" viewBox="0 0 600 120" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 120V70l90-30 110 40 130-55 120 45 150-35v85z" fill="#0f5e4c" opacity=".55"/>
        <path d="M0 120V95l140-30 120 25 160-35 180 30v35z" fill="#0b4a3c"/>
      </svg>
    </section>
    <section class="auth-form"><div class="auth-boite"><slot /></div></section>
  </div>
</template>

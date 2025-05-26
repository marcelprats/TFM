<!-- src/views/PreHome.vue -->
<template>
  <!-- HERO a tota pantalla -->
  <section class="prehome-hero" ref="heroRef">
    <div class="hero-content" :class="{ visible: heroVisible }">
      <h1 class="hero-title">
        Benvingut a <span class="highlight">Totaki</span>
      </h1>
      <p class="hero-subtitle">
        Descobreix els productes i comerços locals més sorprenents.
      </p>
      <button class="scroll-down" @click="scrollToWhat">⌄</button>
    </div>
  </section>

  <!-- “QUÈ ÉS TOTAKI?” -->
  <section class="what-section" ref="whatRef">
    <div class="what-content" :class="{ visible: whatVisible }">
      <h2>Què és <span class="highlight">Totaki</span>?</h2>
      <p class="what-text" :class="{ expanded: expanded }">
        Totaki és l’espai on els petits comerços i artesans del teu barri es troben amb tu.  
        Des de botigues de roba vintage fins a tallers de fusteria, aquí trobaràs productes únics  
        carregats d’història i personalitat. Navega, descobreix i connecta directament amb els veïns  
        del teu barri. Reserva online amb un pagament del 10 % per avançat i recull al local quan  
        estigui preparada la comanda, pagant el 90 % restant al punt de recollida. Gaudeix també  
        de cashback i promocions exclusives que reforcen la comunitat local!<span v-if="!expanded">…</span>
      </p>
      <button v-if="!expanded" class="read-more" @click="expanded = true">
        Mostrar més
      </button>
      <button v-else class="read-less" @click="expanded = false">
        Mostrar menys
      </button>
      <router-link to="/about" class="about">
        Més informació
      </router-link>
    </div>
  </section>

  <!-- Botó per entrar a Home -->
  <router-link to="/home" class="enter-home">
    Entrar a Totaki
  </router-link>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

const heroRef     = ref<HTMLElement|null>(null);
const heroVisible = ref(false);
const whatRef     = ref<HTMLElement|null>(null);
const whatVisible = ref(false);
const expanded    = ref(false);

function scrollToWhat() {
  whatRef.value?.scrollIntoView({ behavior: 'smooth' });
}

onMounted(() => {
  new IntersectionObserver(
    ([e]) => { heroVisible.value = e.isIntersecting; },
    { threshold: 0.3 }
  ).observe(heroRef.value!);

  new IntersectionObserver(
    ([e]) => { whatVisible.value = e.isIntersecting; },
    { threshold: 0.3 }
  ).observe(whatRef.value!);
});
</script>

<style scoped>
/* ─── HERO ───────────────────────────────────────────────── */
.prehome-hero {
  position: relative;
  margin-top: -64px; /* compensa el header fix */
  width: 100vw;
  margin-left: calc(50% - 50vw);
  height: 100vh;
  background: linear-gradient(180deg, #000 0%, #000 10%, #42b983 80%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  z-index: 1;
}

.hero-content {
  text-align: center;
  color: #fff;
  opacity: 0;
  transform: translateY(20px);
  transition: opacity 3s ease, transform 3s ease;
}
.hero-content.visible {
  opacity: 1;
  transform: translateY(0);
}
.hero-title {
  font-size: clamp(2.5rem, 8vw, 4rem);
  margin: 0;
  line-height: 1.1;
  text-shadow: 0 2px 10px rgba(0,0,0,0.6);
}
.hero-subtitle {
  font-size: clamp(1rem, 3vw, 1.5rem);
  margin-top: 1rem;
}
.highlight {
  color: #42b983;
}
.scroll-down {
  margin-top: 2rem;
  font-size: 2rem;
  background: none;
  border: none;
  color: #fff;
  cursor: pointer;
  animation: bounce 2s infinite;
}
@keyframes bounce {
  0%,100% { transform: translateY(0); }
  50%     { transform: translateY(10px); }
}

/* ─── “QUÈ ÉS TOTAKI?” ───────────────────────────────────── */
.what-section {
  padding: 4rem 1rem;
  background: #fff;
  text-align: center;
}
.what-content {
  max-width: 800px;
  margin: 0 auto;
  opacity: 0;
  transform: translateX(-20px);
  transition: opacity 0.8s ease, transform 0.8s ease;
}
.what-content.visible {
  opacity: 1;
  transform: translateX(0);
}
.what-content h2 {
  font-size: 2.5rem;
  color: #064e3b;
  margin-bottom: 1rem;
}
.what-text {
  text-align: justify;
  color: #333;
  margin-top: 1rem;
  max-height: 4.5em;
  overflow: hidden;
  transition: max-height 0.5s ease;
}
.what-text.expanded {
  max-height: 100vh;
}
.read-more,
.read-less {
  margin: 1rem 0.5rem;
  background: #42b983;
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s;
}
.read-more:hover,
.read-less:hover {
  background-color: #fff;
  color: #42b983 !important;
  padding: 0.5rem 1rem;
  /* en lloc de border: */
  box-shadow: inset 0 0 0 2px #42b983;}

/* Botó Llegir més sobre TOTAKI amb fons blanc, text verd i border verd */
.about {
  margin: 1rem 0;
  background-color: #fff;
  color: #42b983 !important;
  padding: 0.5rem 1rem;
  /* en lloc de border: */
  box-shadow: inset 0 0 0 2px #42b983;
  border-radius: 4px;
  text-decoration: none;
  transition: background 0.2s, color 0.2s, box-shadow 0.2s;
}

.about:hover {
  background-color: #42b983;
  color: #fff !important;
  /* mantenim l’inset però amb color invertit per a un petit efecte: */
}



/* BOTÓ d’entrada a Home */
.enter-home {
  display: block;
  text-align: center;
  margin: 2rem auto;
  background: #000;
  color: #fff;
  padding: 1rem 2rem;
  border-radius: 4px;
  width: max-content;
  text-decoration: none;
  transition: background 0.2s;
}
.enter-home:hover {
  background: #333;
}
</style>

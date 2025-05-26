<!-- src/views/QueEsTotaki.vue -->
<template>
  <div class="about-page">
    <!-- Títol i subtítol normals -->
    <header class="page-header">
      <h1>Què és <span class="highlight">Totaki</span>?</h1>
      <p class="subtitle">
        La plataforma per descobrir, connectar i comprar directament a botigues locals
      </p>
    </header>

    <!-- Contingut principal -->
    <section class="about-content">
      <article>
        <h2>La nostra missió</h2>
        <p>
          A Totaki volem empoderar els petits comerços i artesans perquè arribin a més persones
          sense perdre l’essència que els fa únics. Creiem que darrere de cada producte hi ha
          una història i una aposta per la qualitat, la creativitat i la sostenibilitat.
        </p>
      </article>
      
      <article>
        <h2>Per què <span class="highlight">Totaki</span>?</h2>
        <div class="benefits-grid">
          <div>
            <h3>Proper</h3>
            <p>Descobreix comerços que tens al barri.</p>
          </div>
          <div>
            <h3>Sostenible</h3>
            <p>Suport a l’economia local i reducció de la petjada ambiental.</p>
          </div>

        </div>
      </article>

      <article>
        <h2>Com funciona la reserva?</h2>
        <ol>
          <li><strong>Explora</strong>: Navega per centenars de categories—des de moda i decoració fins a art local.</li>
          <li><strong>Descobreix</strong>: Cada botiga disposa de fitxa pròpia amb imatges, descripció i valoracions.</li>
          <li><strong>Reserva online</strong> del producte al teu comerç local favorit.</li>
          <li><strong>Pagues el 10%</strong> del total al fer la reserva (a través de plataforma segura).</li>
          <li><strong>Rebs notificació</strong> quan la comanda està preparada al local.</li>
          <li><strong>Presentes el tiquet</strong> al punt de recollida, pagues el 90% restant i gaudeixes!</li>
        </ol>
        <p class="small">
          Aquest 10% recaptat es destina en part a <strong>cashback</strong> per a venedor i comprador, i també al manteniment i expansió de Totaki.
        </p>
      </article>

      <article>
        <h2>Característiques</h2>
        <ul>
          <li>📍 Mapes interactius per localitzar cada comerç</li>
          <li>🔔 Alertes de novetats i promocions</li>
          <li>💬 Xat en viu amb el venedor</li>
          <li>⭐ Valoracions reals de la comunitat</li>
          <li>⚙️ Filtres per horari, categoria i proximitat</li>
        </ul>
      </article>



      <article class="call-to-action">
        <h2>Uneix-te a Totaki</h2>
        <p>
          Tant si ets client com venedor, Totaki et connecta amb la comunitat local.
        </p>
      </article>

      <!-- Accordion interactiu per comprar/vendre -->
      <div class="accordion">
        <div class="tabs">
          <button 
            :class="{ active: active === 'comprar' }"
            @click="active = 'comprar'"
          >
            Per què comprar?
          </button>
          <div class="divider"></div>
          <button 
            :class="{ active: active === 'vendre' }"
            @click="active = 'vendre'"
          >
            Per què vendre?
          </button>
        </div>

        <transition name="fade" mode="out-in">
          <div v-if="active === 'comprar'" key="comprar" class="panel">
            <ul>
              <li>Accedeix a productes únics del teu barri.</li>
              <li>Reserva i paga un 10 % per avançat, recull quan estigui a punt.</li>
              <li>Gaudeix de cashback i promocions exclusives.</li>
            </ul>
            <router-link to="/register" class="btn-secondary">
              Registra’t com a client
            </router-link>
          </div>

          <div v-else key="vendre" class="panel">
            <ul>
              <li>Obre la teva botiga a milers de clients locals.</li>
              <li>Importació masiva de dades.</li>
              <li>Utilitza Totaki per controlar l'inventari de la teva botiga.</li>              
              <li>Gestiona comandes fàcilment.</li>
              <li>Beneficia’t del nostre programa de fidelització i cashback.</li>
            </ul>
            <router-link to="/register-vendor" class="btn-secondary">
              Registra’t com a venedor
            </router-link>
          </div>
        </transition>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
const active = ref<'comprar' | 'vendre'>('comprar')
</script>

<style scoped>
.about-page {
  max-width: 800px;
  margin: 2rem auto;
  padding: 5rem 2rem;
  font-family: sans-serif;
  color: #333;
  line-height: 1.6;
}

.page-header {
  text-align: center;
  margin-bottom: 2rem;
}
.page-header h1 {
  font-size: 2.75rem;
  color: #064e3b;
  margin: 0;
}
.page-header .subtitle {
  font-size: 1.125rem;
  color: #666;
  margin-top: 0.5rem;
}

.about-content article {
  margin-bottom: 2rem;
}
.about-content h2 {
  font-size: 2rem;
  color: #064e3b;
  margin-bottom: 0.75rem;
}
.about-content p,
.about-content li {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}
.about-content ol {
  padding-left: 1.25rem;
}
.about-content ul {
  list-style: none;
  padding-left: 0;
}
.about-content ul li {
  position: relative;
  padding-left: 1.5rem;
  margin-bottom: 0.5rem;
}
.about-content ul li::before {
  content: "•";
  position: absolute;
  left: 0;
  color: #42b983;
  font-size: 1.25rem;
  line-height: 1;
}

.call-to-action {
  text-align: center;
  margin-bottom: 2.5rem;
}
.call-to-action .btn-primary {
  background: #42b983;
  color: #fff;
  padding: 0.75rem 1.5rem;
  border-radius: 0.25rem;
  text-decoration: none;
  transition: background 0.2s;
}
.call-to-action .btn-primary:hover {
  background: #369e6b;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}
.benefits-grid h3 {
  margin-bottom: 0.5rem;
  color: #42b983;
}

.accordion {
  border-top: 1px solid #ddd;
  padding-top: 2rem;
}
.tabs {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
}
.tabs button {
  flex: 1;
  background: none;
  border: none;
  padding: 0.75rem;
  font-size: 1.1rem;
  cursor: pointer;
  color: #666;
  transition: color 0.2s;
}
.tabs button.active {
  color: #064e3b;
  font-weight: bold;
}
.divider {
  width: 1px;
  height: 1.5rem;
  background: #ccc;
  margin: 0 0.5rem;
}
.panel {
  padding: 1rem 0;
  border-top: 1px solid #eee;
}
.panel ul {
  list-style: none;
  padding-left: 0;
  margin-bottom: 1.5rem;
}
.panel ul li {
  position: relative;
  padding-left: 1.5rem;
  margin-bottom: 0.75rem;
}
.panel ul li::before {
  content: "✔";
  position: absolute;
  left: 0;
  color: #42b983;
}

.btn-secondary {
  display: inline-block;
  background: #42b983;
  color: #fff;
  padding: 0.5rem 1.25rem;
  border-radius: 0.25rem;
  text-decoration: none;
  transition: background 0.2s;
}
.btn-secondary:hover {
  background: #369e6b;
}

/* Transicions fade per a l’accordion */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ressalt */
.highlight {
  color: #42b983;
}
</style>

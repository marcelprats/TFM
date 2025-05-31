<template>
  <div class="about-page">
      <div class="hero-content fade-in visible">
        <h1>
          Què és <span class="highlight">Totaki</span>?
        </h1>
        <p class="subtitle">
          La plataforma per descobrir, connectar i comprar directament a botigues locals
        </p>
      </div>

    <section class="about-content">
      <article class="wow fade-in">
        <h2>La nostra missió</h2>
        <p>
          A Totaki volem empoderar els petits comerços i artesans perquè arribin a més persones
          sense perdre l’essència que els fa únics. Creiem que darrere de cada producte hi ha
          una història i una aposta per la qualitat, la creativitat i la sostenibilitat.
        </p>
      </article>
      
      <article class="benefits-section wow fade-in">
        <h2>Per què <span class="highlight">Totaki</span>?</h2>
        <div class="benefits-grid">
          <div class="benefit-card">
            <div class="emoji">🏠</div>
            <h3>Proper</h3>
            <p>Descobreix comerços que tens al barri.</p>
          </div>
          <div class="benefit-card">
            <div class="emoji">🌱</div>
            <h3>Sostenible</h3>
            <p>Suport a l’economia local i reducció de la petjada ambiental.</p>
          </div>
        </div>
      </article>

      <article class="steps-section wow fade-in">
        <h2>Com funciona la reserva?</h2>
        <div class="steps-grid">
          <div class="step-card">
            <div class="step-number">1</div>
            <div>
              <strong>Explora</strong>: Navega per centenars de categories—des de moda i decoració fins a art local.
            </div>
          </div>
          <div class="step-card">
            <div class="step-number">2</div>
            <div>
              <strong>Descobreix</strong>: Cada botiga disposa de fitxa pròpia amb imatges, descripció i valoracions.
            </div>
          </div>
          <div class="step-card">
            <div class="step-number">3</div>
            <div>
              <strong>Reserva online</strong> del producte al teu comerç local favorit.
            </div>
          </div>
          <div class="step-card">
            <div class="step-number">4</div>
            <div>
              <strong>Pagues el 10%</strong> del total al fer la reserva (a través de plataforma segura).
            </div>
          </div>
          <div class="step-card">
            <div class="step-number">5</div>
            <div>
              <strong>Rebs notificació</strong> quan la comanda està preparada al local.
            </div>
          </div>
          <div class="step-card">
            <div class="step-number">6</div>
            <div>
              <strong>Presentes el tiquet</strong> al punt de recollida, pagues el 90% restant i gaudeixes!
            </div>
          </div>
        </div>
        <p class="small">
          Aquest 10% recaptat es destina en part a <strong>cashback</strong> per a venedor i comprador, i també al manteniment i expansió de Totaki.
        </p>
      </article>

      <article class="features-section wow fade-in">
        <h2>Característiques</h2>
        <ul class="features-list">
          <li><span class="emoji">📍</span> Mapes interactius per localitzar cada comerç</li>
          <li><span class="emoji">🔔</span> Alertes de novetats i promocions</li>
          <li><span class="emoji">💬</span> Xat en viu amb el venedor</li>
          <li><span class="emoji">⭐</span> Valoracions reals de la comunitat</li>
          <li><span class="emoji">⚙️</span> Filtres per horari, categoria i proximitat</li>
        </ul>
      </article>

      <article class="call-to-action wow fade-in">
        <h2>Uneix-te a Totaki</h2>
        <p>
          Tant si ets client com venedor, Totaki et connecta amb la comunitat local.
        </p>
      </article>

      <div class="accordion wow fade-in">
        <div class="tabs">
          <button 
            :class="{ active: active === 'comprar' }"
            @click="active = 'comprar'"
          >
            🛒 Per què comprar?
          </button>
          <div class="divider"></div>
          <button 
            :class="{ active: active === 'vendre' }"
            @click="active = 'vendre'"
          >
            🏪 Per què vendre?
          </button>
        </div>

        <transition name="fade" mode="out-in">
          <div v-if="active === 'comprar'" key="comprar" class="panel panel-buy">
            <ul>
              <li>Accedeix a productes únics del teu barri.</li>
              <li>Reserva i paga un 10 % per avançat, recull quan estigui a punt.</li>
              <li>Gaudeix de cashback i promocions exclusives.</li>
            </ul>
            <div class="panel-btn-row">
              <router-link to="/register" class="btn-secondary btn-big">
                🚀 Registra’t com a client
              </router-link>
            </div>
          </div>
          <div v-else key="vendre" class="panel panel-sell">
            <ul>
              <li>Obre la teva botiga a milers de clients locals.</li>
              <li>Importació massiva de dades.</li>
              <li>Utilitza Totaki per controlar l'inventari de la teva botiga.</li>              
              <li>Gestiona comandes fàcilment.</li>
              <li>Beneficia’t del nostre programa de fidelització i cashback.</li>
            </ul>
            <div class="panel-btn-row">
              <router-link to="/register-vendor" class="btn-secondary btn-big">
                🚀 Registra’t com a venedor
              </router-link>
            </div>
          </div>
        </transition>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
const active = ref<'comprar' | 'vendre'>('comprar')

// Animació fade-in
function handleScroll() {
  document.querySelectorAll('.wow').forEach(el => {
    const rect = el.getBoundingClientRect()
    if (rect.top < window.innerHeight - 70) el.classList.add('visible')
    else el.classList.remove('visible')
  })
}
onMounted(() => {
  handleScroll()
  window.addEventListener('scroll', handleScroll, { passive: true })
})
</script>

<style scoped>
.about-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 0 5rem 0;
  font-family: 'Inter', system-ui, sans-serif;
  color: #233;
  background: none;
}

/* HERO */
.hero {
  position: relative;
  text-align: center;
  padding: 0 0 3.5rem 0;
  min-height: 320px;
  overflow: hidden;
  border-radius: 0 0 2.5rem 2.5rem;
  box-shadow: 0 6px 32px 0 rgba(66,185,131,0.08);
  background: #fff;
  margin-bottom: 0.5rem;
}
.hero-content {
  text-align: center;
  z-index: 2;
  padding: 3.6rem 1.2rem 0 1.2rem;
}
.hero h1 {
  font-size: 2.3rem;
  font-weight: 800;
  color: #064e3b;
  margin: 0 0 0.8rem 0;
  letter-spacing: -1px;
  line-height: 1.13;
}
.hero .highlight {
  color: #42b983;
  font-weight: 900;
  font-size: 1em;
  letter-spacing: -2px;
  margin: 0 0.1em;
}
.hero .subtitle {
  font-size: 1.13rem;
  color: #274549;
  margin-bottom: 0;
}
.hero-img {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 340px;
  max-width: 55vw;
  min-width: 120px;
  opacity: 0.19;
  z-index: 1;
  filter: blur(1px);
  pointer-events: none;
}
.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg,rgba(255,255,255,0.88) 75%,rgba(255,255,255,0.99) 100%);
  z-index: 1;
}

/* --- SECCIONS --- */
.about-content article,
.accordion {
  margin: 0 auto 2.5rem auto;
  background: #fff;
  border-radius: 18px;
  padding: 2.2rem 1.7rem;

  transition: box-shadow .22s, border .22s;
  max-width: 680px;
}

.about-content h2 {
  font-size: 1.3rem;
  color: #064e3b;
  margin-bottom: 1.1rem;
  font-weight: 700;
}
.about-content p,
.about-content li {
  font-size: 1.05rem;
  margin-bottom: 0.5rem;
}

/* BENEFICIS */
.benefits-section { border-left: 7px solid #42b98322; }
.benefits-grid {
  display: flex;
  gap: 1.5rem;
  margin-top: 1.2rem;
}
.benefit-card {
  flex: 1 1 160px;
  background: #f7fff7;
  border-radius: 13px;
  padding: 1.2rem 1rem;
  box-shadow: 0 2px 8px 0 rgba(66,185,131,0.07);
  text-align: center;
}
.benefit-card .emoji {
  font-size: 2.3rem;
  margin-bottom: 0.7rem;
}

.steps-section { border-right: 7px solid #42b98322; }
.steps-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 1.3rem;
  margin-top: 1.3rem;
}
.step-card {
  flex: 1 1 220px;
  min-width: 170px;
  background: #f5fffa;
  border-radius: 11px;
  padding: 1.1rem 1rem;
  margin-bottom: 0.3rem;
  box-shadow: 0 2px 10px 0 rgba(66,185,131,0.05);
  display: flex;
  align-items: flex-start;
  gap: 1.1rem;
}
.step-number {
  min-width: 2.2em;
  min-height: 2.2em;
  background: #42b983;
  color: #fff;
  border-radius: 50%;
  font-weight: 700;
  font-size: 1.23em;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 0.6em;
  margin-top: 0.18em;
  box-shadow: 0 2px 8px 0 #42b98344;
}

/* FEATURES */
.features-section { border-left: 7px solid #42b98322; }
.features-list {
  list-style: none;
  padding-left: 0;
  margin-top: 1.3rem;
}
.features-list li {
  font-size: 1.03rem;
  margin-bottom: 0.72rem;
  display: flex;
  align-items: center;
  gap: 0.7em;
}
.features-list .emoji {
  font-size: 1.5rem;
  margin-right: 0.3em;
}

/* CTA */
.call-to-action {
  text-align: center;
  margin-bottom: 2.7rem;
  border-right: 7px solid #42b98322;
}
.call-to-action h2 {
  font-size: 1.10rem;
  margin-bottom: 0.5rem;
}

/* --- ACCORDION --- */
.accordion {
  padding-top: 2.4rem;
  margin-top: 1.7rem;
}
.tabs {
  display: flex;
  align-items: center;
  margin-bottom: 1rem;
  gap: 0.8rem;
}
.tabs button {
  flex: 1;
  background: none;
  border: none;
  padding: 1.1rem;
  font-size: 1rem;
  cursor: pointer;
  color: #777;
  border-radius: 0.7rem 0.7rem 0 0;
  transition: color 0.2s, background 0.2s;
  font-weight: 600;
  outline: none;
}
.tabs button.active {
  color: #064e3b;
  background: #e6fffa;
  font-weight: bold;
}
.divider {
  width: 1px;
  height: 1.5rem;
  background: #ccc;
}
.panel {
  padding: 1.35rem 0 0.2rem 0;
  min-height: 110px;
  animation: panelIn 0.5s cubic-bezier(.4,1.2,.4,1);
  text-align: center;
}
@keyframes panelIn {
  from { opacity:0; transform: translateY(22px);}
  to {opacity:1; transform:none;}
}
.panel ul {
  list-style: none;
  padding-left: 0;
  margin-bottom: 1.5rem;
  text-align: left;
  display: inline-block;
}
.panel ul li {
  position: relative;
  padding-left: 1.5rem;
  margin-bottom: 0.75rem;
  font-size: 1.01rem;
}
.panel ul li::before {
  content: "✔";
  position: absolute;
  left: 0;
  color: #42b983;
}
.panel-btn-row {
  display: flex;
  justify-content: center;
  margin-top: 1.1rem;
}
.btn-secondary {
  display: inline-block;
  background: #42b983;
  color: #fff;
  padding: 1.15rem 2.7rem;
  border-radius: 0.8rem;
  text-decoration: none;
  transition: background 0.22s, transform .18s;
  font-weight: 700;
  font-size: 1.13rem;
  margin-bottom: 0.7rem;
  box-shadow: 0 3px 16px 0 rgba(66,185,131,0.13);
  letter-spacing: .02em;
}
.btn-secondary:hover {
  background: #25324d;
  color: #fff;
  transform: scale(1.04);
}
.btn-big { font-size: 1.17rem; padding: 1.15rem 2.1rem; border-radius: 1.2rem; }

.small { font-size: .98em; color: #3c6; margin-top: .9em; }

.highlight { color: #42b983; }

.fade-in, .wow {
  opacity: 0;
  transform: translateY(34px) scale(.98);
  transition: opacity .7s cubic-bezier(.4,1.2,.4,1), transform .6s cubic-bezier(.4,1.2,.4,1);
}
.fade-in.visible, .wow.visible {
  opacity: 1;
  transform: none;
}

@media (max-width: 1000px) {
  .about-page { max-width: 100vw; }
  .hero-img { width: 200px; }
  .hero-content { padding-top: 2.1rem; }
}
@media (max-width: 700px) {
  .about-page {
    margin: 10px ;
    padding: 0 0 2.5rem 0;
  }
  .hero-content { padding-top: 1.1rem; }
  .hero { min-height: 150px; border-radius: 0 0 1rem 1rem; }
  .hero h1 { font-size: 1.25rem; }
  .hero-img { width: 80px; min-width: 45px; }
  .about-content article, .accordion { padding: 1rem 0.4rem; border-radius: 9px; }
  .benefits-grid, .steps-grid { flex-direction: column; gap: 0.8rem; }
  .step-card { min-width: 0;   flex: 0 1 auto;  height: auto; }
  .panel-btn-row { margin-top: 0.5rem;}
  .btn-secondary, .btn-big { font-size: 0.98rem; padding: 0.9rem 1.1rem; }
}
</style>
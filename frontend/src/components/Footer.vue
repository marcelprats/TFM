<!-- src/components/Footer.vue -->
<template>
  <footer class="site-footer">
    <div class="footer-inner">
      <!-- Contacte -->
      <div class="footer-section">
        <h3>Contacte</h3>
        <p>✉️ <a href="mailto:totakistore@gmail.com" class="footer-link">totakistore@gmail.com</a></p>
        <p>🐦 <a href="https://x.com/totakistore" target="_blank" rel="noopener" class="footer-link">@totakistore</a></p>
      </div>

      <!-- Navegació -->
      <div class="footer-section">
        <h3>Navegació</h3>
        <ul class="footer-nav">
          <li v-for="link in links" :key="link.to">
            <RouterLink
              :to="link.to"
              class="footer-nav-link"
              :class="{ active: route.path === link.to }"
            >
              {{ link.name }}
            </RouterLink>
          </li>
        </ul>
      </div>

      <!-- Accés (no autenticat) -->
      <div class="footer-section" v-if="!loggedIn">
        <h3>Accés</h3>
        <ul class="footer-nav">
          <li>
            <RouterLink to="/login" class="footer-nav-link">Iniciar sessió</RouterLink>
          </li>
          <li>
            <RouterLink to="/register" class="footer-nav-link">Registrar-se</RouterLink>
          </li>
        </ul>
      </div>

      <!-- Àrea Personal (tots usuaris logejats) -->
      <div class="footer-section" v-if="loggedIn">
        <h3>Àrea Personal</h3>
        <ul class="footer-nav">
          <li>
            <RouterLink to="/perfil" class="footer-nav-link">El meu perfil</RouterLink>
          </li>
          <li>
            <RouterLink to="/perfil" class="footer-nav-link">Les meves comandes</RouterLink>
          </li>
          <li>
            <a href="#" @click.prevent="handleLogout" class="footer-nav-link">Tancar sessió</a>
          </li>
        </ul>
      </div>

      <!-- Àrea Privada Venedor -->
      <div class="footer-section" v-if="loggedIn && role === 'vendor'">
        <h3>Àrea Privada Venedor</h3>
        <ul class="footer-nav">
          <li>
            <RouterLink to="/vendor-orders" class="footer-nav-link">Informació de vendes</RouterLink>
          </li>
          <li>
            <RouterLink to="/area-personal-botigues" class="footer-nav-link">Les Meves Botigues</RouterLink>
          </li>
          <li>
            <RouterLink to="/area-personal-productes" class="footer-nav-link">Els Meus Productes</RouterLink>
          </li>
          <li>
            <RouterLink to="/import-record" class="footer-nav-link">Registre d’importació</RouterLink>
          </li>
        </ul>
      </div>

      <!-- Drets -->
      <div class="footer-section">
        <h3>© 2025 Totaki</h3>
        <p>Tots els drets reservats.</p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { logout, isLoggedIn, getUserType } from '../services/authService'

const route    = useRoute()
const router   = useRouter()

// Reactive flags
const loggedIn = ref(isLoggedIn())
const role     = ref(getUserType())

const links = [
  { name: 'Inici',          to: '/home' },
  { name: 'Productes',      to: '/botiga' },
  { name: 'Mapa Botigues',  to: '/mapa-botigues' },
  { name: 'Què és Totaki?', to: '/about' },
  { name: 'Contacte',       to: '/contacte' },
]

function handleLogout() {
  logout()
  // notify other listeners/tabs
  window.dispatchEvent(new Event('authChange'))
  router.push('/')
}

function syncAuth() {
  loggedIn.value = isLoggedIn()
  role.value     = getUserType()
}

onMounted(() => {
  window.addEventListener('storage', syncAuth)
  window.addEventListener('authChange', syncAuth)
})

onBeforeUnmount(() => {
  window.removeEventListener('storage', syncAuth)
  window.removeEventListener('authChange', syncAuth)
})
</script>

<style scoped>
.site-footer {
  position: relative;
  width: 100vw;
  left: 50%;
  margin-left: -50vw;
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
}
.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
  display: flex;
  flex-wrap: wrap;
  gap: 2rem;
  justify-content: space-between;
}
.footer-section {
  flex: 1 1 200px;
}
.footer-section h3 {
  margin-bottom: 0.75rem;
  color: #064e3b;
  font-size: 1.1rem;
}
.footer-nav {
  list-style: none;
  padding: 0;
  margin: 0;
}
.footer-nav li {
  margin-bottom: 0.5rem;
}
.footer-link,
.footer-nav-link {
  color: #374151;
  text-decoration: none;
  transition: color 0.2s;
}
.footer-link:hover,
.footer-nav-link:hover {
  color: #42b983;
}
.footer-nav-link.active {
  color: #42b983;
  font-weight: bold;
}

/* Responsive */
@media (max-width: 600px) {
  .footer-inner {
    flex-direction: column;
    text-align: center;
  }
  .footer-section {
    margin-bottom: 1.5rem;
  }
}
</style>

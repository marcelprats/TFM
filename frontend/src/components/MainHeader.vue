<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAttrs } from 'vue';
import axios from 'axios';
import { isLoggedIn, getUser, fetchUser, logout, getUserType } from '../services/authService';

const API_URL = 'http://127.0.0.1:8000/api'; // Ajusta la URL de l'API si cal

// Variables reactives i utilitats
const router = useRouter();
const attrs = useAttrs();
const loggedIn = ref(false);
const user = ref(null);
const menuOpen = ref(false);
const infoOpen = ref(false);
const personalOpen = ref(false);
const role = ref("user");

// Variables per gestionar el menú desplegable i el carro
const infoDropdownRef = ref<HTMLElement | null>(null);
const personalDropdownRef = ref<HTMLElement | null>(null);
const cart = ref<any>(null); // Aquí s'emmagatzema la resposta de l'API del carro

// Funció per carregar el carro a través de l'API
async function loadCart() {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/cart`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    cart.value = response.data;
  } catch (error) {
    console.error('Error carregant el carret:', error);
  }
}

// Computed que retorna el nombre total d'articles sumant les quantitats de cada ítem del carro
const cartItemCount = computed(() => {
  if (!cart.value || !cart.value.cart_items) return 0;
  return cart.value.cart_items.reduce((acc: number, item: any) => acc + item.quantity, 0);
});

// Funcions per gestionar el menú i els desplegables
const toggleMenu = () => {
  menuOpen.value = !menuOpen.value;
};

const toggleInfo = () => {
  infoOpen.value = !infoOpen.value;
};

const togglePersonal = () => {
  personalOpen.value = !personalOpen.value;
};

const handleOutsideClick = (event: MouseEvent) => {
  if (infoDropdownRef.value && !infoDropdownRef.value.contains(event.target as Node)) {
    infoOpen.value = false;
  }
  if (personalDropdownRef.value && !personalDropdownRef.value.contains(event.target as Node)) {
    personalOpen.value = false;
  }
};

const handleLogout = async () => {
  await logout();
  loggedIn.value = false;
  user.value = null;
  role.value = "user";
  router.push('/');
};

// Polling: variable per l'interval
let cartInterval: number;

onMounted(async () => {
  document.addEventListener('click', handleOutsideClick);
  loggedIn.value = isLoggedIn();
  user.value = getUser();
  role.value = getUserType();
  if (loggedIn.value && !user.value) {
    user.value = await fetchUser();
  }
  await loadCart();
  // Actualitza el carro cada 10 segons
  cartInterval = window.setInterval(async () => {
    await loadCart();
  }, 10000);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick);
  clearInterval(cartInterval);
});
</script>

<template>
  <header class="main-header" v-bind="attrs">
    <div class="container">
      <!-- LOGO A L'ESQUERRA -->
      <router-link to="/" class="logo">TOTAKI</router-link>

      <!-- TOP BAR PER A MÒBIL (carro + botó hamburguesa) -->
      <!-- Visible només en dispositius <= 768px o quan vulguis -->
      <div class="mobile-controls">
        <!-- Carro en mòbil (icona en blanc) -->
        <router-link to="/cart" class="mobile-cart">
          <div class="cart-icon-wrapper">
            <i class="fa-solid fa-cart-shopping" style="color: white;"></i>
            <span v-if="cartItemCount > 0" class="cart-badge">{{ cartItemCount }}</span>
          </div>
        </router-link>
        <!-- Botó hamburguesa -->
        <button class="menu-toggle" @click="toggleMenu" :class="{ open: menuOpen }" aria-label="Obrir menú">
          <span></span><span></span><span></span>
        </button>
      </div>

      <!-- MENÚ principal -->
      <nav :class="['nav-links', { open: menuOpen }]">
        <!-- Enllaços principals -->
        <router-link to="/" @click="menuOpen = false">Inici</router-link>
        <router-link to="/botiga" @click="menuOpen = false">Botiga</router-link>
        
        <details class="dropdown" ref="infoDropdownRef" :open="infoOpen">
          <summary @click.prevent="toggleInfo">Informació</summary>
          <div class="dropdown-content">
            <router-link to="/info-botiga" @click="menuOpen = false">Llistat Botigues</router-link>
            <router-link to="/mapa-botigues" @click="menuOpen = false">Mapa Botigues</router-link>
            <router-link to="/info-venedor" @click="menuOpen = false">Llistat Venedors</router-link>
          </div>
        </details>

        <details v-if="loggedIn && role === 'vendor'" class="dropdown" ref="personalDropdownRef" :open="personalOpen">
          <summary @click.prevent="togglePersonal">Àrea Personal</summary>
          <div class="dropdown-content">
            <router-link to="/vendor-orders" @click="menuOpen = false">Informació de vendes</router-link>
            <router-link to="/area-personal-botigues" @click="menuOpen = false">Les Meves Botigues</router-link>
            <router-link to="/area-personal-productes" @click="menuOpen = false">Els Meus Productes</router-link>
            <router-link to="/import-record" @click="menuOpen = false">Registre d'importació</router-link>
          </div>
        </details>
        
        <!-- Secció d'autenticació / Carro (ESCRIPTORI) -->
        <!-- Carro al costat del "Hola, X" en mode escriptori -->
        <div class="auth">
          <template v-if="loggedIn">
            <!-- En mode escriptori, posem el carro aquí amb la icona en blanc -->
            <router-link to="/cart" class="desktop-cart" @click="menuOpen = false">
              <div class="cart-icon-wrapper">
                <i class="fa-solid fa-cart-shopping" style="color: white;"></i>
                <span v-if="cartItemCount > 0" class="cart-badge">{{ cartItemCount }}</span>
              </div>
            </router-link>
            <router-link to="/perfil" class="btn btn-hello" @click="menuOpen = false">
              Hola, {{ user?.name }}
            </router-link>
            <button @click="handleLogout" class="btn btn-logout">Tancar Sessió</button>
          </template>
          <template v-else>
            <!-- Si no està loguejat, carro i enllaços de login/registre -->
            <router-link to="/cart" class="desktop-cart">
              <div class="cart-icon-wrapper">
                <i class="fa-solid fa-cart-shopping" style="color: white;"></i>
                <span v-if="cartItemCount > 0" class="cart-badge">{{ cartItemCount }}</span>
              </div>
            </router-link>
            <router-link to="/login" class="auth-link">Login</router-link>
            <router-link to="/register" class="auth-link">Registrar-se</router-link>
          </template>
        </div>
      </nav>
    </div>
  </header>
</template>


<style scoped>
.main-header {
  background-color: #42b983;
  color: #fff;
  width: 100%;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 9000;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  font-size: 1.75rem;
  font-weight: bold;
  color: white;
  text-decoration: none;
}

.menu-toggle {
  display: none;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  margin-right: 0.5rem;
  width: 30px;
  height: 30px;
  position: relative;
}

.menu-toggle span {
  position: absolute;
  width: 24px;
  height: 3px;
  background: white;
  border-radius: 2px;
  transition: 0.3s ease;
}

.menu-toggle span:nth-child(1) {
  top: 6px;
}

.menu-toggle span:nth-child(2) {
  top: 13px;
}

.menu-toggle span:nth-child(3) {
  top: 20px;
}

.menu-toggle.open span:nth-child(1) {
  transform: rotate(45deg);
  top: 13px;
}

.menu-toggle.open span:nth-child(2) {
  opacity: 0;
}

.menu-toggle.open span:nth-child(3) {
  transform: rotate(-45deg);
  top: 13px;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-left: auto;
  transition: max-height 0.3s ease, opacity 0.3s ease;
}

.nav-links a {
  color: white;
  text-decoration: none;
  font-weight: 500;
}

.nav-links a:hover {
  text-decoration: underline;
}

.nav-links.desktop-nav {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.auth {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.auth-link {
  font-weight: bold !important;
  color: white;
  text-decoration: none;
}

.auth-link:hover {
  text-decoration: underline;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 40px;
  padding: 0 16px;
  border-radius: 6px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: 0.2s ease;
  background: white;
  text-decoration: none;
}

.btn-hello {
  color: #42b983 !important;
  font-weight: bold !important;
}

.btn-logout {
  color: #e63946;
}

.btn:hover {
  background: #f1f1f1;
}

.user-name {
  color: #42b983;
}

.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown summary {
  list-style: none;
  cursor: pointer;
  color: white;
  font-weight: 500;
  padding: 0.4rem 0;
  display: inline-block;
}

.dropdown[open] summary::after {
  content: "";
}

.dropdown-content {
  display: none;
  flex-direction: column;
  background: white;
  padding: 0.5rem;
  border-radius: 6px;
  position: absolute;
  top: calc(100% + 0.5rem);
  left: 0;
  min-width: 180px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  z-index: 1001;
}

.dropdown[open] .dropdown-content {
  display: flex;
}

.dropdown-content a {
  color: #333;
  padding: 0.4rem 0.6rem;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 500;
}

.dropdown-content a:hover {
  background-color: #f1f1f1;
}

/* Estils per a la icona i badge del carro */
.cart-icon-wrapper {
  position: relative;
  display: inline-block;
}

.cart-icon-wrapper i {
  font-size: 1.6rem;
}

.cart-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background-color: red;
  color: white;
  font-size: 0.7rem;
  padding: 1px 3px;
  border-radius: 50%;
  min-width: 16px;
  text-align: center;
}

.mobile-controls {
  display: none; /* Per defecte amagat en escriptori */
}

.desktop-cart {
  display: inline-block; /* Visible en escriptori */
  margin-right: 1rem;
  margin-left: 2rem;
}

@media (max-width: 768px) {
  .menu-toggle {
    display: flex;
  }

  .nav-links {
    position: absolute;
    top: 80px;
    left: 0;
    right: 0;
    background: #42b983;
    width: 100%;
    flex-direction: column;
    padding: 1rem;
    display: none;
    align-items: flex-end;
    animation: slideFadeIn 0.3s ease forwards;
  }

  .nav-links.open {
    display: flex;
  }

  .nav-links > * {
    width: auto;
    text-align: right;
  }

  .dropdown-content {
    position: relative;
    background: transparent;
    box-shadow: none;
    padding-left: 0;
    padding-right: 0;
    align-items: flex-end;
  }

  .dropdown-content a {
    color: white;
    padding: 0.3rem 0;
  }

  .auth {
    flex-direction: column;
    align-items: flex-end;
    gap: 0.2rem;
    margin-top: 1rem;
  }

  .auth .btn {
    background: transparent;
    color: white !important;
    font-weight: bold;
    height: auto;
    padding: 0.3rem 0;
    border: none;
    width: auto;
  }

  .auth .btn:hover {
    background: transparent;
    text-decoration: underline;
  }

  .auth a {
    color: white;
    text-decoration: none;
    font-weight: bold;
    padding: 0.3rem 0;
  }

  .auth a:hover {
    text-decoration: underline;
  }

    .mobile-controls {
    display: flex;
    /* Si vols el carro a l’esquerra i el botó a la dreta, pots fer: */
    justify-content: flex-end;
    gap: 1rem;
  }
  
  .desktop-cart {
    display: none; /* Per no duplicar el carro en mòbil */
  }
  
  .menu-toggle {
    display: block;
  }
}

@keyframes slideFadeIn {
  0% {
    opacity: 0;
    transform: translateY(-10px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

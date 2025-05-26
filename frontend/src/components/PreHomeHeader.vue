<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useAttrs } from 'vue';
import { isLoggedIn, getUser, fetchUser, logout, getUserType } from '../services/authService';
import { useCartStore } from '../stores/cartStore';
import { useProducts } from '../composables/useProducts';

const router = useRouter();
const attrs = useAttrs();

// Estat usuaris
const loggedIn = ref(false);
const user = ref<any>(null);
const role = ref('user');

// Estat del menú i dropdowns
const menuOpen = ref(false);
const infoOpen = ref(false);
const personalOpen = ref(false);
const infoDropdownRef = ref<HTMLElement|null>(null);
const personalDropdownRef = ref<HTMLElement|null>(null);

// Carretó
const cartStore = useCartStore();
const cartItemCount = computed(() => cartStore.itemCount);

// Cerca live
const { products: allProducts, loading: loadingProducts } = useProducts();
const showSearch = ref(false);
const searchQuery = ref('');
const results = ref<typeof allProducts.value>([]);
const searchInputRef = ref<HTMLInputElement|null>(null);

async function toggleSearch() {
  showSearch.value = !showSearch.value;
  if (showSearch.value) {
    await nextTick();
    searchInputRef.value?.focus();
  }
}
watch(searchQuery, q => {
  const term = q.trim().toLowerCase();
  results.value = term
    ? allProducts.value
        .filter(p => p.nom.toLowerCase().includes(term))
        .sort((a,b)=>a.nom.localeCompare(b.nom))
        .slice(0,50)
    : [];
});
function goToProduct(id:number) {
  showSearch.value = false;
  router.push(`/producte/${id}`);
}
function fullSearch() {
  showSearch.value = false;
  router.push({ path:'/botiga', query:{ q: searchQuery.value } });
}

// Dropdown handlers
function toggleMenu() { menuOpen.value = !menuOpen.value; }
function toggleInfo() { infoOpen.value = !infoOpen.value; }
function togglePersonal() { personalOpen.value = !personalOpen.value; }
function closeAll() { menuOpen.value = infoOpen.value = personalOpen.value = false; }
function handleOutsideClick(e: MouseEvent) {
  if (infoDropdownRef.value && !infoDropdownRef.value.contains(e.target as Node)) infoOpen.value = false;
  if (personalDropdownRef.value && !personalDropdownRef.value.contains(e.target as Node)) personalOpen.value = false;
}

// Utilitats
function getImageSrc(p:string|null){ const B='http://localhost:8000'; if(!p) return '/img/no-imatge.jpg'; return p.startsWith('/')?B+p:`${B}/uploads/${p}`; }
function formatPrice(v:number|string){ const n=typeof v==='number'?v:parseFloat(v as string); return isNaN(n)?'—':n.toFixed(2)+' €'; }

// Autenticació
async function handleLogout(){
  await logout(); cartStore.$reset();
  loggedIn.value = false; user.value = null; role.value='user';
  router.push('/');
}

// Init
onMounted(async () => {
  document.addEventListener('click', handleOutsideClick);
  loggedIn.value = isLoggedIn();
  if(loggedIn.value){ user.value = getUser()||await fetchUser(); role.value = getUserType(); }
  await cartStore.fetchCart();
});
onBeforeUnmount(()=> document.removeEventListener('click', handleOutsideClick));
</script>

<template>
  <header class="main-header" v-bind="attrs">
    <div class="container">
      <router-link to="/" class="logo">TOTAKI</router-link>

      <!-- ─── Mobile controls (always visible) ─────────────────── -->
      <div class="mobile-controls">
        <button class="icon-btn mobile-search" @click="toggleSearch" aria-label="Cerca">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
        <router-link to="/cart" class="mobile-cart">
          <div class="cart-icon-wrapper">
            <i class="fa-solid fa-cart-shopping"></i>
            <span v-if="cartItemCount>0" class="cart-badge">{{ cartItemCount }}</span>
          </div>
        </router-link>
        <button class="menu-toggle" @click="toggleMenu" :class="{ open: menuOpen }">
          <span/><span/><span/>
        </button>
      </div>

      <!-- ─── Nav + desktop icons + mobile-expanded ─────────────── -->
      <nav :class="['nav-links', { open: menuOpen }]">
        <!-- Links -->
        <router-link to="/home" @click="closeAll">Inici</router-link>
        <router-link to="/botiga" @click="closeAll">Botiga</router-link>

        <!-- “Informació” dropdown -->
        <details class="dropdown" ref="infoDropdownRef" :open="infoOpen">
          <summary @click.prevent="toggleInfo">Informació</summary>
          <div class="dropdown-content">
            <router-link to="/info-botiga" @click="closeAll">Llistat Botigues</router-link>
            <router-link to="/mapa-botigues" @click="closeAll">Mapa Botigues</router-link>
            <router-link to="/info-venedor" @click="closeAll">Llistat Venedors</router-link>
          </div>
        </details>

        <router-link to="/about" @click="closeAll">Què és Totaki?</router-link>
        <router-link to="/contacte" @click="closeAll">Contacte</router-link>

        <!-- “Àrea Personal” dropdown -->
        <details v-if="loggedIn && role==='vendor'" class="dropdown" ref="personalDropdownRef" :open="personalOpen">
          <summary @click.prevent="togglePersonal">Àrea Personal</summary>
          <div class="dropdown-content">
            <router-link to="/vendor-orders" @click="closeAll">Informació de vendes</router-link>
            <router-link to="/area-personal-botigues" @click="closeAll">Les Meves Botigues</router-link>
            <router-link to="/area-personal-productes" @click="closeAll">Els Meus Productes</router-link>
            <router-link to="/import-record" @click="closeAll">Registre d’importació</router-link>
          </div>
        </details>

        <!-- ─── Desktop icons (hidden in mobile-expanded) ──────── -->
        <button class="icon-btn desktop-search" @click="toggleSearch" aria-label="Cerca">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
        <router-link to="/cart" class="desktop-cart">
          <div class="cart-icon-wrapper">
            <i class="fa-solid fa-cart-shopping"></i>
            <span v-if="cartItemCount>0" class="cart-badge">{{ cartItemCount }}</span>
          </div>
        </router-link>

        <!-- Auth -->
        <div class="auth">
          <template v-if="loggedIn">
            <router-link to="/perfil" class="btn btn-hello" @click="closeAll">Hola, {{ user?.name }}</router-link>
            <button class="btn btn-logout" @click="handleLogout">Tancar Sessió</button>
          </template>
          <template v-else>
            <router-link to="/login" class="auth-link" @click="closeAll">Login</router-link>
            <router-link to="/register" class="auth-link" @click="closeAll">Registrar-se</router-link>
          </template>
        </div>
      </nav>
    </div>

    <!-- ─── Popup Cerca ─────────────────────────────────────────── -->
    <transition name="fade">
      <div v-if="showSearch" class="search-popup" @click.self="toggleSearch">
        <div class="search-box">
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            placeholder="🔍 Cerca producte..."
            class="popup-input"
          />
          <p v-if="searchQuery" class="search-counter">
            Trobats: {{ results.length }} {{ results.length === 1 ? 'producte' : 'productes' }}
          </p>
          <button class="full-btn" @click="fullSearch">Búsqueda completa</button>
          <button class="close-btn" @click="toggleSearch">&times;</button>
          <div v-if="loadingProducts" class="input-spinner"></div>

          <div v-if="searchQuery && results.length" class="popup-results">
            <div
              v-for="p in results"
              :key="p.id"
              class="search-card-vertical"
              @click="goToProduct(p.id)"
            >
              <img :src="getImageSrc(p.imatge)" class="search-image-vertical" />
              <div class="search-info-vertical">
                <h3>{{ p.nom }}</h3>
                <p>{{ formatPrice(p.preu) }}</p>
              </div>
            </div>
          </div>
          <p v-else-if="searchQuery && !loadingProducts" class="no-results">
            Cap producte trobat.
          </p>
        </div>
      </div>
    </transition>
  </header>
</template>

<style scoped>
/* ─── HEADER BÀSIC ───────────────────────────────────────────────── */
.main-header {
  background:rgb(0, 0, 0);
  position: fixed; top:0; left:0;
  width:100%; z-index:10000;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.container {
  max-width:1200px; margin:0 auto; padding:0 1rem;
  height:80px; display:flex; align-items:center; justify-content:space-between;
}
.logo {
  font-size:1.75rem; font-weight:bold;
  color:white; text-decoration:none;
}
.btn-hello {
  color: black !important;
}
.btn-logout {
  background: #e63946 !important;
  color: white !important;
}
/* ─── MOBILE CONTROLS ────────────────────────────────────────── */
.mobile-controls { display:none; }
.icon-btn {
  background:none; border:none; color:white;
  font-size:1.25rem; padding:.5rem; cursor:pointer;
}
.mobile-cart i, .desktop-cart i {
  color:white; font-size:1.6rem;
}
/* ─── BADGE RODONA I POSICIONADA CORRECTE ─────────────────────── */
.cart-icon-wrapper {
  position: relative;
}
.cart-badge {
  position: absolute;
  top: -8px;       /* una mica més ajustat */
  right: -8px;
  background: red;
  color: white;
  font-size: 0.65rem;
  padding: 1.5px;
  border-radius: 50%; /* cercle perfecte */
  min-width: 14px;
  min-height: 14px;
  line-height: 1;
  text-align: center;
}
.menu-toggle {
  display:none; flex-direction:column;
  justify-content:center; align-items:center;
  margin-left:.5rem; position:relative; width:30px; height:30px;
}
.menu-toggle span {
  position:absolute; width:24px; height:3px;
  background:white; border-radius:2px;
  transition:.3s ease;
}

.menu-toggle,
.menu-toggle.open {
  background: transparent !important;
}
.menu-toggle span:nth-child(1){top:6px;}
.menu-toggle span:nth-child(2){top:13px;}
.menu-toggle span:nth-child(3){top:20px;}
.menu-toggle.open span:nth-child(1){transform:rotate(45deg); top:13px;}
.menu-toggle.open span:nth-child(2){opacity:0;}
.menu-toggle.open span:nth-child(3){transform:rotate(-45deg); top:13px;}

/* ─── NAV LINKS ───────────────────────────────────────────────── */
.nav-links {
  display:flex; align-items:center; gap:1rem;
  margin-left:auto;
  transition: max-height .3s, opacity .3s;
}
.nav-links > * { margin-right:.5rem; }
.nav-links > a, .nav-links > details > summary {
  position:relative; color:white; text-decoration:none; font-weight:500;
  padding:0 .25rem; cursor:pointer;
}

.nav-links > a::after, .nav-links > details > summary::after {
  content:""; position:absolute; left:0; bottom:-4px;
  width:0; height:2px; background:white; transition:width .3s;
}
.nav-links > a:hover::after,
.nav-links > details > summary:hover::after,
.nav-links > a.router-link-active::after,
.nav-links > details[open] > summary::after {
  width:100%;
}

/* ─── DROPDOWN “Informació” ───────────────────────────────────── */
.dropdown {
  position: relative; 
}
.dropdown-content {
  display: none;
  position: absolute;
  top: calc(100% + 4px); 
  left: 0;
  background: white;     
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  z-index: 10001;
}
.dropdown[open] .dropdown-content { display:flex; flex-direction:column; }
.dropdown-content a {
  display:block; padding:.5rem 1rem; color:#333;
}
.dropdown-content a:hover { background:rgb(75, 75, 75); }

/* ─── Forcem que el text del summary i dels enllaços no salti de línia ───────────────── */
.dropdown summary,
.dropdown-content a {
  white-space: nowrap;
}

/* ─── Fem el fons del dropdown del mateix verd que el header i el text blanc ───────────── */
.dropdown-content {
  background:rgb(0, 0, 0) !important;
}
.dropdown-content a {
  color: white !important;
}

/* ─── AUTH ─────────────────────────────────────────────────────── */
.auth { display:flex; align-items:center; gap:.5rem; }
.auth-link { color:white; font-weight:bold; text-decoration:none; }
.btn {
  background:white; color:rgb(0, 0, 0);
  padding:0 .75rem; height:36px;
  border:none; border-radius:4px; cursor:pointer;
  font-weight:600;
}

/* ─── POPUP CERCA ─────────────────────────────────────────────── */
.fade-enter-active,.fade-leave-active{transition:opacity .2s}
.fade-enter-from,.fade-leave-to{opacity:0}

.search-popup {
  position:fixed; inset:0; background:rgba(0,0,0,.4);
  display:flex; justify-content:center; align-items:flex-start;
  padding:2rem; z-index:11000;
}
.search-box {
  background:white; border-radius:.5rem; padding:1rem;
  width:100%; max-width:600px; position:relative;
}
.popup-input {
  width:100%; padding:.75rem 1rem;
  border:1px solid #ccc; border-radius:.25rem;
  margin-bottom:.5rem; box-sizing:border-box;
}
.search-counter {
  text-align:center; margin-bottom:.5rem;
  color:#374151; font-size:.9rem;
}
.full-btn {
  display:block; width:100%; padding:.5rem;
  margin-bottom:.75rem; background:rgb(0, 0, 0);
  color:white; border:none; border-radius:.25rem;
  cursor:pointer;
}
.full-btn:hover { background:#369e6b; }
.close-btn {
  position:absolute; top:.5rem; right:.5rem;
  background:none; border:none; font-size:1.25rem;
  cursor:pointer;
}
.input-spinner {
  position:absolute; top:1rem; right:1rem;
  width:16px; height:16px;
  border:2px solid #ccc; border-top-color:rgb(0, 0, 0);
  border-radius:50%; animation:spin .7s linear infinite;
}
@keyframes spin{to{transform:rotate(360deg);}}

.popup-results {
  display:block; max-height:60vh; overflow-y:auto; margin-top:1rem;
}
.search-card-vertical {
  display:flex; gap:1rem; align-items:center;
  background:white; border-radius:.5rem; padding:.75rem;
  margin-bottom:.75rem; box-shadow:0 2px 6px rgba(0,0,0,0.1);
  transition:transform .2s,box-shadow .2s;
}
.search-card-vertical:hover {
  transform:translateY(-2px); box-shadow:0 4px 10px rgba(0,0,0,0.15);
}
.search-image-vertical {
  width:64px; height:64px; object-fit:cover; border-radius:.25rem;
}
.search-info-vertical h3{margin:0;color:#333;font-size:1rem;}
.search-info-vertical p{margin:4px 0 0;color:rgb(0, 0, 0);font-weight:500;}
.no-results{ text-align:center; color:#666; margin:1rem 0; }


.auth .btn-hello {
  display: inline-flex;
  align-items: center;   /* vertical centering */
  justify-content: center;
  height: 36px;          /* igual que la resta de botons */
  line-height: 1;        /* perquè el text quedi centrat */
}

/* ─── RESPONSIVE ───────────────────────────────────────────────── */
@media (max-width: 768px) {
  /* Mostrem icones mòbil alineades verticalment */
  .mobile-controls {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-end;
  }

  /* Botó hamburguesa */
  .menu-toggle {
    display: flex;
    background: transparent !important;
  }

  /* Nav col·lapsada */
  .nav-links {
    position: absolute;
    top: 80px;
    left: 0;
    right: 0;
    background:rgb(0, 0, 0);
    flex-direction: column;
    padding: 1rem;
    display: none;
    align-items: flex-end;
    animation: slideFadeIn 0.3s ease forwards;
  }
  .nav-links.open {
    display: flex;
  }

  /* Amaguem el cercador i el carro dins el menú mòbil */
  .nav-links.open .desktop-cart,
  .nav-links.open .desktop-search {
    display: none !important;
  }

  /* Tots els enllaços alineats a la dreta */
  .nav-links > * {
    width: 100%;
    text-align: right;
    margin: 0;
  }

  /* Contenidor principal conserva centrat vertical els controls */
  .container {
    align-items: center;
  }

  /* Dropdown inline dins el menú */
  .nav-links.open .dropdown-content {
    position: static;
    background: transparent;
    box-shadow: none;
    padding: 0;
    width: 100%;
    margin: 0;
  }
  /* Links dins el dropdown ara com elements de menú normals */
  .nav-links.open .dropdown-content a {
    color: white;
    font-weight: 400;
    padding: 0.5rem 0;
    text-align: right;
  }

  /* Alineació botons d’autenticació */
  .nav-links.open .auth {
    display: flex;
    justify-content: flex-end;
    width: 100%;
    margin-top: 0.5rem;
  }
  .nav-links.open .auth .btn,
  .nav-links.open .auth .auth-link {
    width: auto;
    margin-left: 0.5rem;
  }

  /* Ajustem l’ample de la línia als enllaços principals actius */
  .nav-links.open > a.router-link-active {
    display: inline-block;
    width: auto;
    padding: 0.5rem 0;
  }
  .nav-links.open > a.router-link-active::after {
    width: 100%;
  }

  /* I per als summaries cuan un fill està actiu */
  .nav-links.open details.dropdown:has(.dropdown-content .router-link-active) > summary {
    display: inline-block;
    width: auto;
    padding: 0.5rem 0;
  }
  .nav-links.open details.dropdown:has(.dropdown-content .router-link-active) > summary::after {
    width: 100%;
  }
  /* Mostra una fletxa al costat del summary si algun enllaç del seu dropdown està actiu */
.details.dropdown:has(.dropdown-content .router-link-active) > summary::before {
  content: "▸";      /* pots canviar per ▶ o qualsevol altra fletxa */
  display: inline-block;
  margin-right: 0.5rem;
}
}


/* ─── Animacions ───────────────────────────────────────── */
@keyframes slideFadeIn {
  0%   { opacity: 0; transform: translateY(-10px); }
  100% { opacity: 1; transform: translateY(0); }
}

@keyframes fadeDropdown {
  from { opacity: 0; transform: translateY(-5px); }
  to   { opacity: 1; transform: translateY(0); }
}

</style>

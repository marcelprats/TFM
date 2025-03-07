<script setup lang="ts">
import { ref, onMounted } from "vue";
import { isLoggedIn, getUser, logout, fetchUser } from "../services/authService";
import { useRouter } from "vue-router";

const router = useRouter();

const loggedIn = ref(isLoggedIn());
const user = ref(getUser());

const showInfoDropdown = ref(false); // Estat del menú Informació

onMounted(async () => {
  if (loggedIn.value && !user.value) {
    user.value = await fetchUser();
  }
});

const handleLogout = () => {
  logout();
  loggedIn.value = false;
  user.value = null;
  router.push("/");
};
</script>

<template>
  <header class="main-header">
    <div class="container">
      <h1 class="logo">TOTAKI</h1>
      <nav class="navigation">
        <router-link to="/">Inici</router-link>
        <router-link to="/botiga">Botiga</router-link>

        <!-- 🔹 Desplegable "Informació" amb hover -->
        <div class="dropdown"
          @mouseenter="showInfoDropdown = true"
          @mouseleave="showInfoDropdown = false">
          <button class="dropbtn">Informació ▼</button>
          <div v-if="showInfoDropdown" class="dropdown-content">
            <router-link to="/info-botiga">Llistat Botigues</router-link>
            <router-link to="/mapa-botigues">Mapa Botigues</router-link>
            <router-link to="/info-venedor">Llistat Venedors</router-link>
          </div>
        </div>

        <!-- 🔹 Àrea Personal -->
        <div class="dropdown">
          <router-link to="/area-personal">Àrea Personal ▼</router-link>
          <div class="dropdown-content">
            <router-link to="/area-personal-botigues">Botigues</router-link>
            <router-link to="/area-personal-productes">Productes</router-link>
          </div>
        </div>
      </nav>

      <div class="auth">
        <template v-if="loggedIn">
          <router-link to="/perfil" class="user-name">Hola, {{ user?.name }}</router-link>
          <button @click="handleLogout" class="logout-btn">Tancar Sessió</button>
        </template>
        <template v-else>
          <router-link to="/login">Login</router-link>
          <router-link to="/register">Registrar-se</router-link>
        </template>
      </div>
    </div>
  </header>
</template>

<style scoped>
.main-header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  background: #42b983;
  color: white;
  z-index: 1000;
  height: 80px;
  display: flex;
  align-items: center;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  height: 100%;
}

.logo {
  font-size: 40px;
  font-weight: bold;
  text-align: left;
  flex: 1;
}

.navigation {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-right: 40px;
}

.navigation a {
  color: white;
  text-decoration: none;
  font-weight: bold;
}

.navigation a:hover {
  text-decoration: underline;
}

.auth {
  display: flex;
  align-items: center;
  gap: 15px;
}

.auth a {
  font-weight: bold;
  padding: 8px 12px;
  border-radius: 5px;
  background: #42b983;
  color: #f9f9f9;
  outline: 2px solid #f9f9f9;
}

.auth a:hover {
  background: #f9f9f9;
  color: #42b983;
  outline: none;
}

.user-name {
  font-weight: bold;
  text-decoration: none;
  padding: 8px 12px;
  border-radius: 5px;
  background: #f9f9f9;
  color: #42b983;
  outline: none;
}

.user-name:hover {
  background: #000000;
  color: #f9f9f9;
}

.logout-btn {
  background: red;  
  border: none;
  font-weight: bold;
  color: #f9f9f9;
  padding: 10.5px 12px;
  border-radius: 5px;
  outline: 2px solid red;
}

.logout-btn:hover {
  background: #f9f9f9;
  color: red;
  outline: none;
}

/* 🔹 Estil dels desplegables */
.dropdown {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  min-width: 160px;
}

.dropbtn {
  background: transparent;
  border: none;
  color: white;
  font-weight: bold;
  font-size: 16px;
  cursor: pointer;
  padding: 8px 12px;
  width: 100%;
  text-align: center;
  display: block;
  min-width: 160px;
}

.dropdown-content {
  text-align: center;
  display: none;
  position: absolute;
  background-color: white;
  min-width: 160px;
  box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
  z-index: 1;
  border-radius: 5px;
  top: 100%;
  left: 0;
}

/* 🔹 Mostrem el desplegable en hover */
.dropdown:hover .dropdown-content,
.dropdown-content:hover {
  display: block;
}

.dropdown-content a {
  color: black;
  padding: 12px 16px;
  display: block;
  text-decoration: none;
}

.dropdown-content a:hover {
  background-color: #f1f1f1;
}
</style>

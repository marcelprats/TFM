<script setup lang="ts">
import { ref, onMounted } from "vue";
import { isLoggedIn, getUser, logout, fetchUser } from "../services/authService";
import { useRouter } from "vue-router";

const router = useRouter();

const loggedIn = ref(isLoggedIn());
const user = ref(getUser());

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
        <div class="dropdown">
          <button class="dropbtn">Àrea Personal</button>
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

.user-name {
  font-weight: bold;
  text-decoration: none;
  color: white;
  padding: 8px 12px;
  border-radius: 5px;
  background: #1e2d40;
}

.user-name:hover {
  background: #2c3e50;
}

.logout-btn {
  background: red;
  border: none;
  font-weight: bold;
  color: white;
  padding: 10.5px 12px;
  cursor: pointer;
  border-radius: 5px;
}

.logout-btn:hover {
  background: darkred;
}

.auth a {
  color: white;
  text-decoration: none;
  font-weight: bold;
  padding: 8px 12px;
  border-radius: 5px;
  background: #2c3e50;
}

.auth a:hover {
  background: #1e2d40;
}

/* Estil del desplegable */
.dropdown {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  min-width: 160px; /* Ajusta perquè tingui la mateixa amplada que el desplegable */
}

.dropbtn {
  background: transparent;
  border: none;
  color: white;
  font-weight: bold;
  font-size: 16px;
  cursor: pointer;
  padding: 8px 12px;
  width: 100%; /* Assegura que ocupa tot l'espai disponible */
  text-align: center;
  display: block;
  min-width: 160px; /* Mateixa amplada que el desplegable */
}

.dropdown-content {
  display: none;
  position: absolute;
  background-color: white;
  min-width: 160px; /* Mateixa amplada que el botó */
  box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
  z-index: 1;
  border-radius: 5px;
  top: 100%; /* Col·loca el desplegable just sota el botó */
  left: 0; /* Assegura que està alineat correctament amb el botó */
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

.dropdown:hover .dropdown-content {
  display: block;
}
</style>

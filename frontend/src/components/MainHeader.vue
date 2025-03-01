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

const links = [
  { path: "/", name: "Inici" },
  { path: "/shop", name: "Botiga" },
  { path: "/about", name: "Sobre Nosaltres" },
  { path: "/contact", name: "Contacte" },
];
</script>

<template>
  <header class="main-header">
    <div class="container">
      <h1 class="logo">TOTAKI</h1>
      <nav class="navigation">
        <router-link v-for="(link, index) in links" :key="index" :to="link.path">
          {{ link.name }}
        </router-link>
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
  color: white;
  padding: 8px 12px;
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
</style>

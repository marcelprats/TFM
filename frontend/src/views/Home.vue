<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";

const users = ref<{ id: number; name: string; email: string }[]>([]);

onMounted(async () => {
  try {
    const response = await axios.get("http://127.0.0.1:8000/api/users");
    users.value = response.data;
  } catch (error) {
    console.error("Error carregant usuaris:", error);
  }
});
</script>

<template>
  <div class="home">
    <h1>Benvingut a la plataforma per al comerç local!</h1>

    <div class="user-list">
      <h2>Usuaris Registrats</h2>
      <ul v-if="users.length">
        <li v-for="user in users" :key="user.id">
          {{ user.name }} - {{ user.email }}
        </li>
      </ul>
      <p v-else>No hi ha usuaris registrats.</p>
    </div>
  </div>
</template>

<style scoped>
.home {
  text-align: center;
  margin-top: 50px;
}

.user-list {
  margin-top: 30px;
  padding: 20px;
  border-radius: 10px;
  background: white;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
  display: inline-block;
}

ul {
  list-style: none;
  padding: 0;
}

li {
  padding: 10px;
  border-bottom: 1px solid #ddd;
}

li:last-child {
  border-bottom: none;
}
</style>

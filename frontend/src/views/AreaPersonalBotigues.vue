<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";

const API_URL = "http://127.0.0.1:8000/api";
const botigues = ref<{ id: number; nom: string; descripcio: string }[]>([]);
const newBotiga = ref({ nom: "", descripcio: "" });
const errorMessage = ref("");

const fetchBotigues = async () => {
  try {
    const token = localStorage.getItem("userToken"); // 🔹 Obtenim el token
    if (!token) {
      console.error("No hi ha cap token d'autenticació.");
      return;
    }

    const response = await axios.get(`${API_URL}/botigues`, {
      headers: {
        Authorization: `Bearer ${token}`, // 🔹 Afegim el token
      },
    });

    botigues.value = response.data; // ✅ Guardem les botigues carregades
    console.log("Botigues carregades:", botigues.value);
  } catch (error) {
    console.error("Error carregant botigues:", error);
  }
};

const addBotiga = async () => {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) {
      errorMessage.value = "No s'ha trobat el token d'autenticació.";
      return;
    }

    await axios.post(`${API_URL}/botigues`, newBotiga.value, {
      headers: {
        Authorization: `Bearer ${token}`, // ✅ Afegim el token
      },
    });

    newBotiga.value = { nom: "", descripcio: "" };
    fetchBotigues(); // 🔄 Tornem a carregar la llista de botigues
  } catch (error) {
    errorMessage.value = "Error afegint botiga.";
    console.error(error);
  }
};

const deleteBotiga = async (id: number) => {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) {
      errorMessage.value = "No s'ha trobat el token d'autenticació.";
      return;
    }

    await axios.delete(`${API_URL}/botigues/${id}`, {
      headers: {
        Authorization: `Bearer ${token}`, // ✅ Afegim el token
      },
    });

    fetchBotigues(); // 🔄 Tornem a carregar la llista de botigues
  } catch (error) {
    errorMessage.value = "Error eliminant botiga.";
    console.error(error);
  }
};

onMounted(fetchBotigues);
</script>

<template>
  <div>
    <h1>Gestió de Botigues</h1>
    <div>
      <input v-model="newBotiga.nom" placeholder="Nom de la botiga" />
      <input v-model="newBotiga.descripcio" placeholder="Descripció" />
      <button @click="addBotiga">Afegir Botiga</button>
    </div>
    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
    <ul>
      <li v-for="botiga in botigues" :key="botiga.id">
        {{ botiga.nom }} - {{ botiga.descripcio }}
        <button @click="deleteBotiga(botiga.id)">Eliminar</button>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.error {
  color: red;
  font-weight: bold;
}
</style>

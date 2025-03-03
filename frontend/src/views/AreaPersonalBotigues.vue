<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";

const API_URL = "http://127.0.0.1:8000/api";
const botigues = ref<{ id: number; nom: string; descripcio: string }[]>([]);
const searchQuery = ref("");
const newBotiga = ref({ nom: "", descripcio: "" });
const editBotiga = ref<{ id: number; nom: string; descripcio: string } | null>(null);
const deleteBotigaId = ref<number | null>(null);
const showDeleteModal = ref(false);
const showEditModal = ref(false);
const showAddModal = ref(false);
const errorMessage = ref("");

const fetchBotigues = async () => {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) {
      console.error("No hi ha cap token d'autenticació.");
      return;
    }
    const response = await axios.get(`${API_URL}/botigues`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    botigues.value = response.data;
  } catch (error) {
    console.error("Error carregant botigues:", error);
  }
};

const router = useRouter();

const filteredBotigues = computed(() => {
  return botigues.value.filter(botiga =>
    botiga.nom.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const addBotiga = async () => {
  try {
    const token = localStorage.getItem("userToken");
    await axios.post(`${API_URL}/botigues`, newBotiga.value, {
      headers: { Authorization: `Bearer ${token}` },
    });
    newBotiga.value = { nom: "", descripcio: "" };
    showAddModal.value = false;
    fetchBotigues();
  } catch (error) {
    errorMessage.value = "Error afegint botiga.";
  }
};

const openEditBotiga = (botiga: { id: number; nom: string; descripcio: string }) => {
  editBotiga.value = { ...botiga };
  showEditModal.value = true;
};

const updateBotiga = async () => {
  if (editBotiga.value) {
    try {
      const token = localStorage.getItem("userToken");
      await axios.put(`${API_URL}/botigues/${editBotiga.value.id}`, {
        nom: editBotiga.value.nom,
        descripcio: editBotiga.value.descripcio
      }, {
        headers: { Authorization: `Bearer ${token}` }
      });

      showEditModal.value = false;
      fetchBotigues();
    } catch (error) {
      errorMessage.value = "Error actualitzant botiga.";
    }
  }
};

const confirmDeleteBotiga = (id: number) => {
  deleteBotigaId.value = id;
  showDeleteModal.value = true;
};

const deleteBotiga = async () => {
  if (deleteBotigaId.value !== null) {
    try {
      const token = localStorage.getItem("userToken");
      await axios.delete(`${API_URL}/botigues/${deleteBotigaId.value}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      showDeleteModal.value = false;
      fetchBotigues();
    } catch (error) {
      errorMessage.value = "Error eliminant botiga.";
    }
  }
};

const goToProductes = () => {
  router.push("/area-personal-productes");
};

onMounted(fetchBotigues);
</script>

<template>
  <div class="container">
    <h1>Gestió de Botigues</h1>
    
    <div class="search-container">
      <input v-model="searchQuery" placeholder="Cerca botiga..." />

      <button class="add-btn" @click="showAddModal = true">➕ Afegir Botiga</button>

      <button class="add-btn" @click="goToProductes">Productes</button>

    </div>
    
    <ul class="botiga-list">
      <li v-for="botiga in filteredBotigues" :key="botiga.id">
        <div>
          <strong>{{ botiga.nom }}</strong> - {{ botiga.descripcio }}
        </div>
        <div class="botiga-actions">
          <button class="edit-btn" @click="openEditBotiga(botiga)">✏️ Editar</button>
          <button class="delete-btn" @click="confirmDeleteBotiga(botiga.id)">🗑️ Eliminar</button>
        </div>
      </li>
    </ul>

    <!-- Finestra modal de confirmació d'eliminació -->
    <div v-if="showDeleteModal" class="modal">
      <div class="modal-content">
        <p>Segur que vols eliminar aquesta botiga?</p>
        <button @click="deleteBotiga" class="confirm-btn">Sí, eliminar</button>
        <button @click="showDeleteModal = false" class="delete-btn">Cancel·lar</button>
      </div>
    </div>

    <!-- Finestra modal per editar botiga -->
    <div v-if="showEditModal && editBotiga" class="modal">
    <div class="modal-content">
        <h3>Editar Botiga</h3>
        
        <table class="modal-table">
          <tbody>
            <tr>
                <td><strong>Nom:</strong></td>
                <td><input v-model="editBotiga.nom" placeholder="Nom de la botiga" /></td>
            </tr>
            <tr>
                <td><strong>Descripció:</strong></td>
                <td><textarea v-model="editBotiga.descripcio" placeholder="Descripció"></textarea></td>
            </tr>
          </tbody>
        </table>

        <div class="modal-actions">
        <button @click="updateBotiga" class="confirm-btn">💾 Desa canvis</button>
        <button @click="showEditModal = false" class="delete-btn">❌ Cancel·lar</button>
        </div>
    </div>
    </div>
        
    <!-- Finestra modal per afegir botiga -->
    <div v-if="showAddModal" class="modal">
      <div class="modal-content">
        <h3>Afegir Botiga</h3>
        <table class="modal-table">
          <tbody>
            <tr>
                <td><strong>Nom:</strong></td>
                <td><input v-model="newBotiga.nom" placeholder="Nom de la botiga" /></td>
            </tr>
            <tr>
                <td><strong>Descripció:</strong></td>
                <td><textarea v-model="newBotiga.descripcio" placeholder="Descripció"></textarea></td>
            </tr>
          </tbody>
        </table>
        <div class="modal-actions">
          <button @click="addBotiga" class="confirm-btn">💾 Desa</button>
          <button @click="showAddModal = false" class="delete-btn">❌ Cancel·lar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.container {
  max-width: 600px;
  margin: auto;
  padding: 20px;
  text-align: center;
}

.add-botiga input {
  margin: 5px;
  padding: 8px;
  width: 200px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

button {
  background: #42b983;
  color: #f9f9f9;
  border: none;
  padding: 8px 12px;
  border-radius: 5px;
  cursor: pointer;
  margin: 10px 0;
}

button:hover {
  background: #f9f9f9;
  color: #368c6e;
  outline: 2px solid #368c6e;
}

.botiga-list {
  list-style: none;
  padding: 0;
  margin-top: 20px;
}

.botiga-list li {
  display: flex;
  justify-content: space-between;
  background: #f9f9f9;
  padding: 10px;
  border-radius: 5px;
  margin-bottom: 10px;
  align-items: center;
}

.botiga-actions {
  display: flex;
  gap: 10px;
}

.edit-btn {
  background: #f0ad4e;
  color: #f9f9f9;
  margin-right: 20px;
}

.edit-btn:hover {
  background: #f9f9f9;
  color: #f0ad4e;
  outline: 2px solid #f0ad4e;
}

.delete-btn {
  background: #d9534f;
  color: #f9f9f9;
}

.delete-btn:hover {
  background: #f9f9f9;
  color: #d9534f;
  outline: 2px solid #d9534f;
}

.confirm-btn {
  background: #42b983;
  color: white;
  margin-left:20px;
  border: none;
}

.confirm-btn:hover {
  background: #f9f9f9;
  color: #42b983;
  outline: 2px solid #42b983;
}

.search-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.search-container input {
  padding: 8px;
  width: 40%;
  border: 1px solid #42b983;
  border-radius: 5px;
  outline: none;
  font-size: 16px;
  transition: border 0.3s ease-in-out;
}

/* Finestra modal */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-content {
  background: white;
  padding: 20px;
  border-radius: 10px;
  text-align: center;
  width: 450px;
}

/* Taula dins la modal */
.modal-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 15px;
}

.modal-table td {
  padding: 8px;
  vertical-align: middle;
}

.modal-table input,
.modal-table textarea {
  width: 100%;
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 5px;
  font-size: 14px;
}

/* Ajustament correcte de mida per la descripció */
.modal-table textarea {
  height: 80px;
  resize: none;
}

/* Botons */
.modal-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 15px;
}

.confirm-btn {
  background: #42b983;
  color: white;
  padding: 8px 15px;
  border-radius: 5px;
}

.cancel-btn {
  background: #d9534f;
  color: white;
  padding: 8px 15px;
  border-radius: 5px;
}
</style>
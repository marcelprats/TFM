<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";

const API_URL = "http://127.0.0.1:8000/api";
const productes = ref<{ id: number; nom: string; preu: number; botigues: any[] }[]>([]);
const botigues = ref<{ id: number; nom: string }[]>([]);
const newProduct = ref({ nom: "", descripcio: "", preu: 0, stock: 0, botiga_id: null });
const showAddModal = ref(false);
const errorMessage = ref("");

const fetchProductes = async () => {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) {
      console.error("No hi ha cap token d'autenticació.");
      return;
    }

    const response = await axios.get(`${API_URL}/productes`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    productes.value = response.data;
  } catch (error) {
    console.error("Error carregant productes:", error);
  }
};

const fetchBotigues = async () => {
  try {
    const token = localStorage.getItem("userToken");
    const response = await axios.get(`${API_URL}/botigues`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    botigues.value = response.data;
  } catch (error) {
    console.error("Error carregant botigues:", error);
  }
};

const addProducte = async () => {
  try {
    const token = localStorage.getItem("userToken");
    await axios.post(`${API_URL}/productes`, newProduct.value, {
      headers: { Authorization: `Bearer ${token}` },
    });

    newProduct.value = { nom: "", descripcio: "", preu: 0, stock: 0, botiga_id: null };
    showAddModal.value = false;
    fetchProductes();
  } catch (error) {
    errorMessage.value = "Error afegint producte.";
  }
};

const deleteProducte = async (id: number) => {
  try {
    const token = localStorage.getItem("userToken");
    await axios.delete(`${API_URL}/productes/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    fetchProductes();
  } catch (error) {
    errorMessage.value = "Error eliminant producte.";
  }
};

onMounted(() => {
  fetchProductes();
  fetchBotigues();
});
</script>

<template>
  <div class="container">
    <h1>Gestió de Productes</h1>
    
    <button class="add-btn" @click="showAddModal = true">➕ Afegir Producte</button>

    <ul class="producte-list">
      <li v-for="producte in productes" :key="producte.id">
        <div>
          <strong>{{ producte.nom }}</strong> - {{ producte.preu }}€ ({{ producte.stock }} unitats)
          <p><small>Botigues: {{ producte.botigues ? producte.botigues.map(b => b.nom).join(", ") : "Cap" }}</small></p>
        </div>
        <button class="delete-btn" @click="deleteProducte(producte.id)">🗑️ Eliminar</button>
      </li>
    </ul>

    <!-- Finestra modal per afegir producte -->
    <div v-if="showAddModal" class="modal">
      <div class="modal-content">
        <h3>Afegir Producte</h3>
        
        <table class="modal-table">
          <tbody>
            <tr>
                <td><strong>Nom:</strong></td>
                <td><input v-model="newProduct.nom" placeholder="Nom del producte" /></td>
            </tr>
            <tr>
                <td><strong>Descripció:</strong></td>
                <td><textarea v-model="newProduct.descripcio" placeholder="Descripció"></textarea></td>
            </tr>
            <tr>
                <td><strong>Preu:</strong></td>
                <td><input v-model="newProduct.preu" type="number" placeholder="Preu (€)" /></td>
            </tr>
            <tr>
                <td><strong>Stock:</strong></td>
                <td><input v-model="newProduct.stock" type="number" placeholder="Quantitat" /></td>
            </tr>
            <tr>
                <td><strong>Botiga:</strong></td>
                <td>
                  <select v-model="newProduct.botiga_id">
                    <option v-for="botiga in botigues" :key="botiga.id" :value="botiga.id">
                      {{ botiga.nom }}
                    </option>
                  </select>
                </td>
            </tr>
          </tbody>
        </table>


        <div class="modal-actions">
        <button @click="addProducte" class="confirm-btn">💾 Desa</button>
        <button @click="showAddModal = false" class="cancel-btn">❌ Cancel·lar</button>
        </div>
      </div>
    </div>

    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
  </div>
</template>


<style scoped>
.container {
  max-width: 600px;
  margin: auto;
  padding: 20px;
  text-align: center;
}

.add-product input {
  margin: 5px;
  padding: 8px;
  width: 200px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

button {
  background: #42b983;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 5px;
  cursor: pointer;
  margin: 5px;
}

button:hover {
  background: #368c6e;
}

.product-list {
  list-style: none;
  padding: 0;
  margin-top: 20px;
}

.product-list li {
  display: flex;
  justify-content: space-between;
  background: #f9f9f9;
  padding: 10px;
  border-radius: 5px;
  margin-bottom: 10px;
  align-items: center;
}

.product-actions {
  display: flex;
  gap: 10px;
}

.edit-btn {
  background: #f0ad4e;
}

.delete-btn {
  background: #d9534f;
}

.delete-btn:hover {
  background: #c9302c;
}

/* Estils per a la finestra modal */
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

.modal-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 15px;
}

.modal-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 15px;
}
</style>

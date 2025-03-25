<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import L from "leaflet";
import HorarisEditor from "../components/HorarisEditor.vue";

// 📌 API
const API_URL = "http://127.0.0.1:8000/api";

// 📌 Reactius
const botigues = ref([]);
const searchQuery = ref("");
const router = useRouter();

// 📌 Horaris per defecte
const getDefaultHoraris = () => [
  { dia: "dilluns", tancat: true, franjes: [] },
  { dia: "dimarts", tancat: true, franjes: [] },
  { dia: "dimecres", tancat: true, franjes: [] },
  { dia: "dijous", tancat: true, franjes: [] },
  { dia: "divendres", tancat: true, franjes: [] },
  { dia: "dissabte", tancat: true, franjes: [] },
  { dia: "diumenge", tancat: true, franjes: [] },
];

// 📌 Estat modal
const showAddModal = ref(false);
const showEditModal = ref(false);
const errorMessage = ref("");

// 📌 Botiga nova / editada
const newBotiga = ref({
  nom: "",
  descripcio: "",
  latitude: 41.40945396689205,
  longitude: 2.178125381469727,
  horaris: getDefaultHoraris(),
});

const editBotiga = ref(null);

// 📌 Format horaris rebuts
const formatHoraris = (horarisDB) => {
  const horaris = getDefaultHoraris();
  const formatHora = (hora) => hora.slice(0, 5);
  horarisDB.forEach((h) => {
    const dia = horaris.find((x) => x.dia === h.dia);
    if (dia) {
      dia.tancat = false;
      dia.franjes.push({
        obertura: formatHora(h.obertura),
        tancament: formatHora(h.tancament),
      });
    }
  });
  return horaris;
};

const hores = Array.from({ length: 24 * 4 }, (_, i) => {
  const h = Math.floor(i / 4).toString().padStart(2, "0");
  const m = (i % 4 * 15).toString().padStart(2, "0");
  return `${h}:${m}`;
});

// 📌 Carregar botigues
const fetchBotigues = async () => {
  const token = localStorage.getItem("userToken");
  if (!token) return;

  const res = await axios.get(`${API_URL}/botigues-mes`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  botigues.value = res.data;
};

// 📌 Filtrar
const filteredBotigues = computed(() =>
  botigues.value.filter((b) =>
    b.nom.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
);

// 📌 Mapa
const maps = new Map();

const initMap = (mapId, botiga) => {
  nextTick(() => {
    const el = document.getElementById(mapId);
    if (!el) return;

    // 🔄 Si ja hi ha mapa, eliminar-lo
    if (maps.has(mapId)) {
      maps.get(mapId).remove();
      maps.delete(mapId);
      el.innerHTML = "";
    }

    const map = L.map(el).setView(
      [botiga.latitude ?? 41.40945, botiga.longitude ?? 2.17812],
      14
    );

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const marker = L.marker(
      [botiga.latitude ?? 41.40945, botiga.longitude ?? 2.17812],
      { draggable: true }
    ).addTo(map);

    marker.on("dragend", () => {
      const pos = marker.getLatLng();
      botiga.latitude = parseFloat(pos.lat.toFixed(8));
      botiga.longitude = parseFloat(pos.lng.toFixed(8));
    });

    setTimeout(() => map.invalidateSize(), 200);
    maps.set(mapId, map);
  });
};

// 📌 Obrir modal edició
const openEditBotiga = async (botiga) => {
  editBotiga.value = {
    ...botiga,
    latitude: parseFloat(botiga.latitude),
    longitude: parseFloat(botiga.longitude),
    horaris: botiga.horaris?.length ? formatHoraris(botiga.horaris) : getDefaultHoraris(),
  };
  showEditModal.value = true;
  await nextTick();
  initMap("mapEdit", editBotiga.value);
};

// 📌 Obrir modal nova
const openAddBotiga = async () => {
  newBotiga.value = {
    nom: "",
    descripcio: "",
    latitude: 41.40945396689205,
    longitude: 2.178125381469727,
    horaris: getDefaultHoraris(),
  };
  showAddModal.value = true;
  await nextTick();
  initMap("mapAdd", newBotiga.value);
};

// 📌 Actualitzar
const updateBotiga = async () => {
  const token = localStorage.getItem("userToken");
  await axios.put(`${API_URL}/botigues/${editBotiga.value.id}`, editBotiga.value, {
    headers: { Authorization: `Bearer ${token}` },
  });
  showEditModal.value = false;
  fetchBotigues();
};

// 📌 Afegir
const addBotiga = async () => {
  const token = localStorage.getItem("userToken");
  await axios.post(`${API_URL}/botigues`, newBotiga.value, {
    headers: { Authorization: `Bearer ${token}` },
  });
  showAddModal.value = false;
  fetchBotigues();
};

const deleteBotigaId = ref(null);
const showDeleteModal = ref(false);

// ❌ Confirmar eliminació i obrir modal
const confirmDeleteBotiga = (id) => {
  deleteBotigaId.value = id;
  showDeleteModal.value = true;
};

// ❌ Eliminar botiga
const deleteBotiga = async () => {
  if (deleteBotigaId.value !== null) {
    try {
      const token = localStorage.getItem("userToken");
      await axios.delete(`${API_URL}/botigues/${deleteBotigaId.value}`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      showDeleteModal.value = false;
      fetchBotigues(); // Actualitzar la llista
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

    <!-- 🔍 Buscador i botons d'acció -->
    <div class="search-container">
      <input v-model="searchQuery" placeholder="Cerca botiga..." class="search-input"/>
      <div class="action-buttons">
        <button class="add-btn" @click="openAddBotiga">➕ Afegir Botiga</button>
        <button class="add-btn secondary-btn" @click="goToProductes">📦 Productes</button>
      </div>
    </div>

    <!-- 📋 Llistat -->
    <ul class="botiga-list">
      <li v-for="botiga in filteredBotigues" :key="botiga.id">
        <div class="botiga-info">
          <strong>{{ botiga.nom }}</strong>
        </div>
        <div class="botiga-actions">
          <button @click="openEditBotiga(botiga)" class="btn edit-btn">✏ Editar</button>
          <button @click="confirmDeleteBotiga(botiga.id)" class="btn delete-btn">🗑 Eliminar</button>
        </div>
      </li>
    </ul>

    <!-- 🛠️ Modal Edició -->
    <div v-if="showEditModal" class="modal" @click.self="showEditModal = false">
      <div class="modal-content">
        <h3>Editar Botiga</h3>
        <label>Nom</label>
        <input v-model="editBotiga.nom" />
        <label>Descripció</label>
        <textarea v-model="editBotiga.descripcio"></textarea>

        <div id="mapEdit" class="map-container"></div>

        <label>Latitud</label>
        <input v-model="editBotiga.latitude" readonly />
        <label>Longitud</label>
        <input v-model="editBotiga.longitude" readonly />

        <HorarisEditor v-model:horaris="editBotiga.horaris" />

        <div class="modal-actions">
          <button class="confirm-btn" @click="updateBotiga">💾 Desa</button>
          <button class="delete-btn" @click="showEditModal = false">❌ Cancel·lar</button>
        </div>
      </div>
    </div>

    <!-- ➕ Modal Nova Botiga -->
    <div v-if="showAddModal" class="modal" @click.self="showAddModal = false">
      <div class="modal-content">
        <h3>Afegir Botiga</h3>
        <label>Nom</label>
        <input v-model="newBotiga.nom" />
        <label>Descripció</label>
        <textarea v-model="newBotiga.descripcio"></textarea>

        <div id="mapAdd" class="map-container"></div>

        <label>Latitud</label>
        <input v-model="newBotiga.latitude" readonly />
        <label>Longitud</label>
        <input v-model="newBotiga.longitude" readonly />

        <HorarisEditor v-model:horaris="newBotiga.horaris" />

        <div class="modal-actions">
          <button class="confirm-btn" @click="addBotiga">💾 Desa</button>
          <button class="delete-btn" @click="showAddModal = false">❌ Cancel·lar</button>
        </div>
      </div>
    </div>

    <!-- ➕ Modal Eliminar Botiga -->
    <div v-if="showDeleteModal" class="modal-backdrop">
      <div class="modal">
        <div class="modal-content">
          <h3>Confirmació</h3>
          <p>Segur que vols eliminar aquesta botiga? Aquesta acció és irreversible.</p>
          <div class="modal-actions">
            <button @click="deleteBotiga" class="delete-btn">Sí, eliminar</button>
            <button @click="showDeleteModal = false" class="btn btn-secondary">Cancel·lar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.container {
  max-width: 900px;
  margin: auto;
  padding: 20px;
  text-align: center;
}

.search-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  gap: 10px;
}

.search-input {
  flex: 1;
  min-width: 200px;
  max-width: 300px;
  padding: 6px;
  border-radius: 5px;
  border: 1px solid #ccc;
}

.action-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.add-btn {
  background: #42b983;
  color: white;
  border: none;
  padding: 8px 14px;
  cursor: pointer;
  border-radius: 5px;
  white-space: nowrap;
}

.add-btn:hover {
  background: #368c6e;
}

.secondary-btn {
  background: #f0ad4e;
}

.secondary-btn:hover {
  background: #d99a3e;
}

input, textarea, select {
  width: 100%;
  padding: 8px;
  border-radius: 5px;
  border: 1px solid #ccc;
  margin-bottom: 10px;
}

button {
  background: #42b983;
  color: white;
  border: none;
  padding: 8px 12px;
  cursor: pointer;
  border-radius: 5px;
  margin: 5px;
}

button:hover {
  background: #368c6e;
}

.botiga-list {
  list-style: none;
  padding: 0;
  margin-top: 20px;
}

.botiga-list li {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
  padding: 10px;
  background: #f9f9f9;
  margin-bottom: 10px;
  border-radius: 5px;
}

@media (min-width: 600px) {
  .botiga-list li {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
}

.edit-btn {
  background: #f0ad4e;
}

.edit-btn:hover {
  background: #d99a3e;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  min-height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 25px;
  border-radius: 10px;
  max-width: 600px;
  width: 100%;
  margin-left: auto;
  margin-right: auto;
  max-height: 80vh;
  overflow-y: auto;
  overflow-x: hidden;
  position: relative;
  text-align: left;
  box-sizing: border-box;
}


.map-container {
  width: 100%;
  height: 250px;
  border: 1px solid #ccc;
  border-radius: 10px;
  margin: 10px 0;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 15px;
  flex-wrap: wrap;
}

.confirm-btn {
  background: #42b983;
}

.delete-btn {
  background: #d9534f;
}

.delete-btn:hover {
  background: #c9302c;
}

@media (max-width: 768px) {
  .modal-content {
    margin-left: 16px;
    margin-right: 16px;
  }
}

</style>

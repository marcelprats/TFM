<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from "vue";
import { useToast } from "vue-toastification";
import axios from "axios";
import { useRouter } from "vue-router";
import L from "leaflet";
import HorarisEditor from "../components/HorarisEditor.vue";

const toast = useToast();
const router = useRouter();

// Estado
const botigues = ref<any[]>([]);
const searchQuery = ref("");
const deleteBotigaId = ref<number|null>(null);

const showAddModal    = ref(false);
const showEditModal   = ref(false);
const showDeleteModal = ref(false);
const errorMessage    = ref("");

// Horaris
const getDefaultHoraris = () => [
  { dia: "dilluns",   tancat: true, franjes: [] },
  { dia: "dimarts",   tancat: true, franjes: [] },
  { dia: "dimecres",  tancat: true, franjes: [] },
  { dia: "dijous",    tancat: true, franjes: [] },
  { dia: "divendres", tancat: true, franjes: [] },
  { dia: "dissabte",  tancat: true, franjes: [] },
  { dia: "diumenge",  tancat: true, franjes: [] },
];

const newBotiga = ref({
  nom: "",
  descripcio: "",
  latitude:  41.40945397,
  longitude:  2.17812538,
  horaris: getDefaultHoraris(),
});

const editBotiga = ref<any|null>(null);

type HorariDB = { dia:string, obertura:string, tancament:string }[];

function formatHoraris(hdb: HorariDB) {
  const out = getDefaultHoraris();
  hdb.forEach(h => {
    const day = out.find(d => d.dia === h.dia);
    if (day) {
      day.tancat = false;
      day.franjes.push({
        obertura:  h.obertura.slice(0,5),
        tancament: h.tancament.slice(0,5),
      });
    }
  });
  return out;
}

// Mapa draggable
const maps = new Map<string,L.Map>();
function initMap(id: string, b: any) {
  nextTick(() => {
    const el = document.getElementById(id);
    if (!el) return;
    if (maps.has(id)) {
      maps.get(id)!.remove();
      maps.delete(id);
      el.innerHTML = "";
    }
    const lat = b.latitude ?? 41.40945;
    const lng = b.longitude ?? 2.17812;
    const map = L.map(el).setView([lat,lng],14);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    const marker = L.marker([lat,lng],{ draggable:true }).addTo(map);
    marker.on("dragend", () => {
      const p = marker.getLatLng();
      b.latitude  = parseFloat(p.lat.toFixed(8));
      b.longitude = parseFloat(p.lng.toFixed(8));
    });
    setTimeout(() => map.invalidateSize(),200);
    maps.set(id, map);
  });
}

// CRUD
async function fetchBotigues() {
  try {
    const { data } = await axios.get("/vendor/botigues-mes");
    botigues.value = data;
  } catch (e) {
    console.error("Error carregant botigues:", e);
    errorMessage.value = "No s'han pogut carregar les botigues.";
  }
}

const filteredBotigues = computed(() =>
  botigues.value.filter(b =>
    b.nom.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
);

async function openAddBotiga() {
  newBotiga.value = {
    nom: "", descripcio: "",
    latitude: 41.40945397,
    longitude: 2.17812538,
    horaris: getDefaultHoraris(),
  };
  showAddModal.value = true;
  await nextTick();
  initMap("mapAdd", newBotiga.value);
}

async function addBotiga() {
  try {
    const payload = {
      nom: newBotiga.value.nom,
      descripcio: newBotiga.value.descripcio,
      latitude: newBotiga.value.latitude,
      longitude: newBotiga.value.longitude,
      horaris: newBotiga.value.horaris
        .filter(h => h.franjes.length)
        .map(h=>({ dia: h.dia, franjes: h.franjes }))
    };
    await axios.post("/vendor/botigues", payload);
    toast.success("Botiga afegida correctament ✅");
    showAddModal.value = false;
    await fetchBotigues();
  } catch (e) {
    console.error("Error afegint botiga:", e);
    toast.error("Error afegint botiga ❌");
  }
}

async function openEditBotiga(b: any) {
  editBotiga.value = {
    ...b,
    latitude:  parseFloat(b.latitude),
    longitude: parseFloat(b.longitude),
    horaris:   b.horaris?.length ? formatHoraris(b.horaris) : getDefaultHoraris()
  };
  showEditModal.value = true;
  await nextTick();
  initMap("mapEdit", editBotiga.value);
}

async function updateBotiga() {
  if (!editBotiga.value) return;
  try {
    const payload = {
      nom: editBotiga.value.nom,
      descripcio: editBotiga.value.descripcio,
      latitude: editBotiga.value.latitude,
      longitude: editBotiga.value.longitude,
      horaris: editBotiga.value.horaris
        .filter((h:any)=>h.franjes.length)
        .map((h:any)=>({ dia:h.dia, franjes:h.franjes }))
    };
    await axios.put(`/vendor/botigues/${editBotiga.value.id}`, payload);
    toast.success("Botiga actualitzada correctament ✅");
    showEditModal.value = false;
    await fetchBotigues();
  } catch (e) {
    console.error("Error actualitzant botiga:", e);
    toast.error("Error al desar la botiga ❌");
  }
}

function confirmDeleteBotiga(id: number) {
  deleteBotigaId.value = id;
  showDeleteModal.value = true;
}
async function deleteBotiga() {
  if (deleteBotigaId.value == null) return;
  try {
    await axios.delete(`/vendor/botigues/${deleteBotigaId.value}`);
    showDeleteModal.value = false;
    await fetchBotigues();
  } catch (e) {
    console.error("Error eliminant botiga:", e);
    errorMessage.value = "Error eliminant botiga.";
  }
}

// Navegació
function goToProductes() {
  router.push("/area-personal-productes");
}

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

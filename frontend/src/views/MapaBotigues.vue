<script setup lang="ts">
import { ref, onMounted, computed, watch, onUnmounted } from "vue";
import axios from "axios";
import L from "leaflet";
import { useRouter } from "vue-router";

const API_URL = "http://127.0.0.1:8000/api";
const botigues = ref([]);
const categories = ref([]);
const map = ref(null);
const markers = ref(new Map());
const searchQuery = ref("");
const selectedCategory = ref("");
const showList = ref(false);
const router = useRouter();
const activePopup = ref(null);


// Carregar botigues
const fetchBotigues = async () => {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) {
      console.error("No hi ha cap token d'autenticació.");
      return;
    }

    const response = await axios.get(`${API_URL}/botigues-mes`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    botigues.value = response.data;
    filteredBotigues.value = botigues.value;
    carregarMapa();
    carregarCategories();
  } catch (error) {
    console.error("Error carregant botigues:", error);
  }
};

// Filtrar i ordenar botigues en temps real
const filteredBotigues = computed(() => {
  let resultat = botigues.value;

  if (searchQuery.value) {
    resultat = resultat.filter((botiga) =>
      botiga.nom.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  }

  if (selectedCategory.value) {
    resultat = resultat.filter((botiga) => botiga.categoria === selectedCategory.value);
  }

  // Ordenar alfabèticament per nom
  return resultat.sort((a, b) => a.nom.localeCompare(b.nom));
});


// Carregar categories
const carregarCategories = async () => {
  try {
    const response = await axios.get(`${API_URL}/categories`);
    categories.value = response.data;
  } catch (error) {
    console.error("Error carregant categories:", error);
  }
};

// Carregar mapa
const carregarMapa = () => {
  if (map.value) {
    map.value.remove();
  }

  map.value = L.map("mapContainer").setView([41.3851, 2.1734], 12);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map.value);

  actualitzarMarcadors();
};

// Afegir marcadors
const actualitzarMarcadors = () => {
  markers.value.forEach((marker) => map.value.removeLayer(marker));
  markers.value.clear();

  filteredBotigues.value.forEach((botiga) => {
    if (botiga.latitude && botiga.longitude) {
      const marker = L.marker([botiga.latitude, botiga.longitude])
        .addTo(map.value)
        .bindPopup(
          `<b><a href="#" class="popup-link" data-id="${botiga.id}">${botiga.nom}</a></b>`
        )
        .on("popupopen", () => {
          setTimeout(() => {
            document.querySelectorAll(".popup-link").forEach((el) => {
              el.addEventListener("click", (event) => {
                event.preventDefault();
                const id = event.target.getAttribute("data-id");
                router.push(`/info-botiga/${id}`);
              });
            });
          }, 100);
        });

      markers.value.set(botiga.id, marker);
    }
  });
};

// Filtrar botigues en temps real
watch(searchQuery, (newVal) => {
  if (!newVal) {
    filteredBotigues.value = botigues.value;
  } else {
    filteredBotigues.value = botigues.value.filter((botiga) =>
      botiga.nom.toLowerCase().includes(newVal.toLowerCase())
    );
  }
  actualitzarMarcadors();
});

// Filtrar per categoria
watch(selectedCategory, (newVal) => {
  if (!newVal) {
    filteredBotigues.value = botigues.value;
  } else {
    filteredBotigues.value = botigues.value.filter((botiga) => botiga.categoria === newVal);
  }
  actualitzarMarcadors();
});

// Centrar i obrir popup de la botiga seleccionada
const seleccionarBotiga = (botiga) => {
  if (botiga.latitude && botiga.longitude) {
    map.value.setView([botiga.latitude, botiga.longitude], 15);
    activePopup.value = botiga.id;
    showList.value = false;

    setTimeout(() => {
      const marker = markers.value.get(botiga.id);
      if (marker) {
        marker.openPopup();
      }
    }, 500);
  }
};

// Tancar la llista en fer clic fora
const tancarLlista = (event) => {
  if (!event.target.closest(".search-container")) {
    showList.value = false;
  }
};

// Afegim event listener en muntar
onMounted(() => {
  fetchBotigues();
  document.addEventListener("click", tancarLlista);
});

// Eliminem event listener en desmuntar
onUnmounted(() => {
  document.removeEventListener("click", tancarLlista);
});
</script>

<template>
  <div class="map-page">
    <h1>Mapa de Botigues</h1>

    <!-- 🔍 Buscador i filtres -->
    <div class="controls">
      <div class="search-container">
        <input
          v-model="searchQuery"
          placeholder="🔍 Busca una botiga..."
          @focus="showList = true"
        />
        <div v-if="showList" class="botiga-list">
          <ul>
            <li v-for="botiga in filteredBotigues" :key="botiga.id" @click="seleccionarBotiga(botiga)">
              {{ botiga.nom }}
            </li>
          </ul>
        </div>
      </div>

      <!-- Filtre de categories -->
      <select v-model="selectedCategory">
        <option value="">Totes les categories</option>
        <option v-for="categoria in categories" :key="categoria.id" :value="categoria.nom">
          {{ categoria.nom }}
        </option>
      </select>
    </div>

    <!-- Mapa -->
    <div id="mapContainer"></div>
  </div>
</template>

<style scoped>
.map-page {
  text-align: center;
  padding: 20px;
  position: relative;
}

.controls {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-bottom: 15px;
}

.search-container {
  position: relative;
}

.search-container input {
  padding: 8px;
  width: 250px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

/* Llista de botigues en buscador */
.botiga-list {
  position: absolute;
  top: 35px;
  left: 0;
  width: 250px;
  max-height: 200px;
  overflow-y: auto;
  background: white;
  border: 1px solid #ddd;
  border-radius: 5px;
  box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
  z-index: 2000;
}

.botiga-list ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.botiga-list li {
  padding: 10px;
  cursor: pointer;
  border-bottom: 1px solid #ddd;
}

.botiga-list li:hover {
  background: #f0f0f0;
}

select {
  padding: 8px;
  border-radius: 5px;
  border: 1px solid #ccc;
  cursor: pointer;
}

#mapContainer {
  width: 100%;
  height: 80vh;
  border-radius: 10px;
  box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
}
</style>

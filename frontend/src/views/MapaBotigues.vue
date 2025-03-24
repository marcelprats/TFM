<script setup lang="ts">
import { ref, onMounted, watch, computed, nextTick } from "vue";
import axios from "axios";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

const API_URL = "http://127.0.0.1:8000/api";

const botigues = ref([]);
const filtreDia = ref("");
const filtreHora = ref("");
const obertAra = ref(false);
const llistaQuery = ref("");
const userLocation = ref(null);
const distancies = ref({});
const map = ref(null);
const orderBy = ref("nom");
const mostrarHorari = ref(false);

const selectedBotigaId = ref(null);

const toggleBotigaDetall = (b) => {
  selectedBotigaId.value = selectedBotigaId.value === b.id ? null : b.id;
  mostrarBotiga(b);
};

watch(mostrarHorari, (val) => {
  if (!val) {
    filtreDia.value = "";
    filtreHora.value = "";
  } else {
    obertAra.value = false;
  }
});

watch(obertAra, (val) => {
  if (val) {
    mostrarHorari.value = false;
    filtreDia.value = "";
    filtreHora.value = "";
  }
});


const handleOrderByDistancia = () => {
  if (!userLocation.value) {
    obtenirUbicacio();
  }
  orderBy.value = "distancia";
};


const diesSetmana = ["Dilluns", "Dimarts", "Dimecres", "Dijous", "Divendres", "Dissabte", "Diumenge"];
const hores = Array.from({ length: 24 }, (_, i) => `${i}`);

const botiguesFiltrades = computed(() => {
  const query = llistaQuery.value.toLowerCase();
  const ara = new Date();
  const diaActual = diesSetmana[(ara.getDay() + 6) % 7];
  const minutsActuals = ara.getHours() * 60 + ara.getMinutes();

  return botigues.value
    .filter(b => b.nom.toLowerCase().includes(query))
    .filter(b => {
      if (!b.horaris || !Array.isArray(b.horaris)) {
        return !obertAra.value;
      }
      if (obertAra.value) {
        return b.horaris.some(h => {
          return (
            h.dia.toLowerCase() === diaActual.toLowerCase() &&
            parseInt(h.obertura) * 60 <= minutsActuals &&
            minutsActuals < parseInt(h.tancament) * 60
          );
        });
      } else {
        return b.horaris.some(h => {
          const diaOk = !filtreDia.value || h.dia.toLowerCase() === filtreDia.value.toLowerCase();
          const horaOk = !filtreHora.value || (
            parseInt(h.obertura) <= parseInt(filtreHora.value) &&
            parseInt(filtreHora.value) < parseInt(h.tancament)
          );
          return diaOk && horaOk;
        });
      }

    })
    .sort((a, b) => {
      if (orderBy.value === "distancia") {
        return (distancies.value[a.id] || Infinity) - (distancies.value[b.id] || Infinity);
      } else {
        return a.nom.localeCompare(b.nom);
      }
    });
});

const carregarBotigues = async () => {
  try {
    const token = localStorage.getItem("userToken");
    const res = await axios.get(`${API_URL}/botigues-mes`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    botigues.value = res.data;
    await nextTick();
    renderMap();
  } catch (err) {
    console.error("Error carregant botigues:", err);
  }
};

const calcularDistancia = (lat1, lon1, lat2, lon2) => {
  const R = 6371;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(lat1 * Math.PI / 180) *
            Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) ** 2;
  return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)) * 10) / 10;
};

const horarisPerBotiga = (botiga) => {
  const perDia = {};
  diesSetmana.forEach((dia) => {
    const horarisDia = botiga.horaris?.filter(
      h => h.dia.toLowerCase() === dia.toLowerCase()
    );
    if (horarisDia && horarisDia.length > 0) {
      perDia[dia] = horarisDia.map(h => {
        const ob = h.obertura?.slice(0, 5); // Ex: "09:00"
        const tc = h.tancament?.slice(0, 5);
        return `${ob} - ${tc}`;
      }).join(", ");
    } else {
      perDia[dia] = "Tancat";
    }
  });
  return perDia;
};


const renderMap = () => {
  if (map.value) map.value.remove();
  map.value = L.map("mapa").setView([41.3851, 2.1734], 13);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap",
  }).addTo(map.value);

  const allCoords = [];

  if (userLocation.value) {
    const { lat, lng } = userLocation.value;
    allCoords.push([lat, lng]);

    const redIcon = L.icon({
      iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png",
      shadowUrl: "https://unpkg.com/leaflet@1.9.3/dist/images/marker-shadow.png",
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41],
    });

    L.marker([lat, lng], { icon: redIcon })
      .addTo(map.value)
      .bindPopup("📍 Estàs aquí")
      .openPopup();
  }

  botiguesFiltrades.value.forEach(b => {
    if (b.latitude && b.longitude) {
      allCoords.push([b.latitude, b.longitude]);

      let text = b.nom;
      if (userLocation.value) {
        const d = calcularDistancia(userLocation.value.lat, userLocation.value.lng, b.latitude, b.longitude);
        distancies.value[b.id] = d;
        text += ` (${d} km)`;
      }

      const horarisText = b.horaris?.map(h => `${h.dia}: ${h.obertura} - ${h.tancament}`).join("<br>") || "Horari no disponible";

      const marker = L.marker([b.latitude, b.longitude])
        .addTo(map.value)
        .bindPopup(`<b><a href='/info-botiga/${b.id}'>${text}</a></b>`);

        marker.on("click", () => {
          selectedBotigaId.value = b.id;
          nextTick(() => {
            marker.openPopup();
          });
        });


      b.marker = marker; // vincular amb la llista
    }
  });

  if (allCoords.length > 0) {
    map.value.fitBounds(allCoords, { padding: [30, 30] });
  }
};

const obtenirUbicacio = () => {
  navigator.geolocation.getCurrentPosition(
    pos => {
      userLocation.value = {
        lat: pos.coords.latitude,
        lng: pos.coords.longitude,
      };
      renderMap();
    },
    err => {
      console.error("No es pot obtenir la ubicació", err);
    }
  );
};

const mostrarBotiga = (botiga) => {
  if (botiga.marker) {
    map.value.setView(botiga.marker.getLatLng(), 16, { animate: true });
    botiga.marker.openPopup();
  }
};


watch([filtreDia, filtreHora, obertAra, llistaQuery, orderBy], renderMap);
onMounted(() => {
  carregarBotigues();
});




</script>


<template>
  <div class="map-wrapper">
    <h1>Mapa de Botigues</h1>

    <!-- 🔍 Filtres -->
    <div class="filters">
      <button @click="obertAra = !obertAra" :class="{ active: obertAra }">
        ✅ Obert ara
      </button>
      <button @click="mostrarHorari = !mostrarHorari" :class="{ active: mostrarHorari }">
        🕒 Horari
      </button>

      <template v-if="mostrarHorari">
        <select v-model="filtreDia">
          <option value="">Tots els dies</option>
          <option v-for="d in diesSetmana" :key="d" :value="d">{{ d }}</option>
        </select>

        <select v-model="filtreHora">
          <option value="">Totes les hores</option>
          <option v-for="h in hores" :key="h" :value="parseInt(h)">{{ h }}h</option>
        </select>
      </template>


      <button @click="obtenirUbicacio">📍 La meva ubicació</button>
    </div>



    <!-- 🗺️ Mapa + Llista -->
    <div class="map-layout">
      <div id="mapa" class="mapa-container"></div>

      <aside class="sidebar-list">
        <div class="order-buttons">
          <button @click="orderBy = 'nom'" :class="{ active: orderBy === 'nom' }">🔤 Nom</button>
          <button @click="handleOrderByDistancia" :class="{ active: orderBy === 'distancia' }">📍 Distància</button>
        </div>

        <div class="sidebar-top">
          <input 
            v-model="llistaQuery"
            type="text"
            placeholder="🔍 Cerca una botiga..."
            class="search-sidebar"
          />
        </div>

        <div class="sidebar-scroll">
          <ul class="botiga-cards">
            <li
              v-for="b in botiguesFiltrades"
              :key="b.id"
              @click="toggleBotigaDetall(b)"
              class="botiga-card"
              :class="{ selected: selectedBotigaId === b.id }"
              :aria-expanded="selectedBotigaId === b.id"
            >
              <div class="botiga-header">
                <h4 class="botiga-nom">{{ b.nom }}</h4>
                <p class="botiga-distancia" v-if="distancies[b.id]">📍 {{ distancies[b.id] }} km</p>
              </div>

              <div v-if="selectedBotigaId === b.id" class="detall-botiga">
                <table class="horari-taula">
                  <tbody>
                    <tr v-for="dia in diesSetmana" :key="dia">
                      <td class="dia"><strong>{{ dia }}</strong></td>
                      <td class="horari">{{ horarisPerBotiga(b)[dia] }}</td>
                    </tr>
                  </tbody>
                </table>

                <a :href="'/info-botiga/' + b.id" class="detall-enllac">🔗 Veure informació completa</a>
              </div>
            </li>

          </ul>
        </div>
      </aside>
    </div>

  </div>
</template>

<style scoped>
.map-wrapper {
  padding: 20px;
}

.filters {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;
}

.filters select {
  min-width: 120px;
}
.filters button {
  min-width: 100px;
}

.filters select,
.filters button {
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-weight: bold;
  cursor: pointer;
}

.filters button.active {
  background-color: #42b983;
  color: white;
  border: none;
}

.map-layout {
  display: flex;
  gap: 20px;
}

.mapa-container {
  flex: 2;
  height: 80vh;
  border-radius: 10px;
  box-shadow: 0 4px 14px rgba(0,0,0,0.1);
}

.sidebar-list {
  flex: 1;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 10px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.sidebar-top {
  padding: 12px;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: center;
}

.search-sidebar {
  width: 90%;
  padding: 8px 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 14px;
}

.sidebar-scroll {
  padding: 12px;
  overflow-y: auto;
  flex-grow: 1;
}

.sidebar-scroll ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar-scroll li {
  margin-bottom: 8px;
}

.sidebar-scroll a {
  color: #42b983;
  font-weight: bold;
  text-decoration: none;
}

.sidebar-scroll a:hover {
  text-decoration: underline;
}

.distance {
  color: #777;
  font-size: 12px;
  margin-left: 4px;
}

.botiga-cards {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.botiga-card {
  background: #ffffff;
  padding: 12px 14px;
  border-radius: 8px;
  box-shadow: 0 1px 6px rgba(0,0,0,0.08);
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.botiga-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 3px 12px rgba(0,0,0,0.15);
}

.botiga-nom {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.botiga-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.botiga-distancia {
  font-size: 13px;
  color: #666;
  margin-top: 4px;
}

.horari-taula {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  font-size: 14px;
}

.horari-taula td {
  padding: 6px 4px;
  border-bottom: 1px solid #eee;
  vertical-align: top;
}

.horari-taula .dia {
  width: 100px;
  font-weight: bold;
  color: #333;
}

.horari-taula .horari {
  color: #444;
}

.detall-enllac {
  display: inline-block;
  margin-top: 16px;
  font-weight: bold;
  text-decoration: none;
  color: #42b983;
}

.detall-enllac:hover {
  text-decoration: underline;
}

.order-buttons {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin: 12px 0;
}

/* Responsive per mòbils i tauletes */
@media (max-width: 900px) {
  .map-layout {
    flex-direction: column;
  }

  .mapa-container {
    height: 50vh;
    flex: none;
  }

  .sidebar-list {
    max-height: none;
    flex: none;
    margin-top: 10px;
  }

  .search-sidebar {
    width: 100%;
  }

  .sidebar-scroll {
    max-height: 50vh;
  }
}

.detall-botiga {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}


</style>

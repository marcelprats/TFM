<template>
  <div ref="mapContainer" class="leaflet-container map-full"></div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick, onBeforeUnmount, defineExpose } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

interface Store { id: number; nom: string; latitude: number; longitude: number; }

const props = defineProps<{ stores: Store[] }>()
const mapContainer = ref<HTMLDivElement|null>(null)

let map: L.Map
let markersLayer: L.LayerGroup
const markersMap = new Map<number, L.Marker>()

// Mètode per encaixar tots els marcadors en els límits del mapa
function fitMarkersBounds() {
  const latlngs: L.LatLngExpression[] = []
  props.stores.forEach(s => {
    if (s.latitude != null && s.longitude != null) {
      latlngs.push([s.latitude, s.longitude])
    }
  })
  if (map && latlngs.length) {
    const bounds = L.latLngBounds(latlngs)
    map.fitBounds(bounds, { padding: [40, 40] })
  }
}

onMounted(() => {
  if (!mapContainer.value) return

  // Inicia el mapa
  map = L.map(mapContainer.value)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  markersLayer = L.layerGroup().addTo(map)
  map.invalidateSize()
  addMarkers()
  // Ajust inicial de vista per encaixar tots els marcadors
  fitMarkersBounds()
})


watch(() => props.stores, async () => {
  if (!map) return
  await nextTick()
  map.invalidateSize()
  addMarkers()
  fitMarkersBounds()
})

function addMarkers() {
  markersLayer.clearLayers()
  markersMap.clear()

  props.stores.forEach(s => {
    const marker = L.marker([s.latitude, s.longitude])
    marker.addTo(markersLayer)

    // Bind popup
    marker.bindPopup(
      `<b><a href=\"/info-botiga/${s.id}\" target=\"_blank\">${s.nom}</a></b>`
    )

    // Afegeix llistener per fer flyTo i obrir popup
    marker.on('click', () => {
      map.flyTo([s.latitude, s.longitude], 16, {
        animate: true,
        duration: 0.7
      })
      marker.openPopup()
    })

    markersMap.set(s.id, marker)
  })
}

// Exposem aquests mètodes al component pare
defineExpose({
  zoomToStore(store: Store) {
    const m = markersMap.get(store.id)
    if (map && m) {
      map.flyTo([store.latitude, store.longitude], 16, {
        animate: true,
        duration: 0.7
      })
      m.openPopup()
    }
  },
  setInitialView(latlng: [number, number], zoom: number) {
    if (map) map.setView(latlng, zoom)
  }
})
</script>

<style scoped>
.map-full {
  width: 100%;
  height: 100%;
  flex: 1;
}
.leaflet-container {
  width: 100%;
  height: 100%;
}
</style>

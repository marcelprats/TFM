<template>
  <div class="horaris-container">
    <table class="horaris-table">
      <thead>
        <tr>
          <th>Dia</th>
          <th>Tancat</th>
          <th>Franges horàries</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(horari, index) in horaris" :key="index">
          <td>{{ capitalize(horari.dia) }}</td>
          <td>
            <input
              type="checkbox"
              :checked="horari.tancat"
              @change="toggleTancat(index)"
            />
          </td>
          <td>
            <div v-if="horari.tancat">🔒 Tancat</div>
            <div v-else>
              <div
                v-for="(franja, i) in horari.franjes"
                :key="i"
                class="franja"
              >
                <select
                  :value="franja.obertura"
                  @change="updateFranja(index, i, 'obertura', $event.target.value)"
                  class="hour-select"
                >
                  <option v-for="h in hores" :key="'o' + h" :value="h">{{ h }}</option>
                </select>
                <span>-</span>
                <select
                  :value="franja.tancament"
                  @change="updateFranja(index, i, 'tancament', $event.target.value)"
                  class="hour-select"
                >
                  <option v-for="h in hores" :key="'t' + h" :value="h">{{ h }}</option>
                </select>
                <button @click="eliminarFranja(index, i)" class="delete-btn">❌</button>
              </div>
              <button @click="afegirFranja(index)" class="add-btn">➕ Afegir franja</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'

const props = defineProps<{
  horaris: { dia: string; tancat: boolean; franjes: { obertura: string; tancament: string }[] }[]
}>()

const emit = defineEmits<{
  (e: 'update:horaris', value: typeof props.horaris): void
}>()

const hores = Array.from({ length: 24 * 4 }, (_, i) => {
  const h = Math.floor(i / 4).toString().padStart(2, "0")
  const m = (i % 4 * 15).toString().padStart(2, "0")
  return `${h}:${m}`
})

const capitalize = (text: string) => text.charAt(0).toUpperCase() + text.slice(1)

// NOVA: Còpia profunda
function deepCopy(obj: any) {
  return JSON.parse(JSON.stringify(obj))
}

// Alterna tancat/obert per un dia
function toggleTancat(index: number) {
  const copy = deepCopy(props.horaris)
  copy[index].tancat = !copy[index].tancat
  // Si ara està obert, inicialitza franja
  if (!copy[index].tancat && copy[index].franjes.length === 0) {
    copy[index].franjes.push({ obertura: "09:00", tancament: "13:00" })
  }
  // Si ara està tancat, esborra franges
  if (copy[index].tancat) {
    copy[index].franjes = []
  }
  emit('update:horaris', copy)
}

// Afegeix franja a un dia
function afegirFranja(index: number) {
  const copy = deepCopy(props.horaris)
  const last = copy[index].franjes[copy[index].franjes.length - 1]
  copy[index].franjes.push({
    obertura: last ? last.tancament : "09:00",
    tancament: "13:00"
  })
  emit('update:horaris', copy)
}

// Elimina franja d'un dia
function eliminarFranja(index: number, i: number) {
  const copy = deepCopy(props.horaris)
  copy[index].franjes.splice(i, 1)
  emit('update:horaris', copy)
}

// Actualitza una franja (obertura/tancament)
function updateFranja(index: number, i: number, field: "obertura" | "tancament", value: string) {
  const copy = deepCopy(props.horaris)
  copy[index].franjes[i][field] = value
  emit('update:horaris', copy)
}
</script>

<style scoped>
/* El teu mateix css */
.horaris-container {
  padding: 0 16px;
}

.horaris-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border: 1px solid #ddd;
  border-radius: 10px;
  overflow: hidden;
}

.horaris-table th,
.horaris-table td {
  padding: 10px;
  text-align: center;
  border-bottom: 1px solid #eee;
}

.franja {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 5px;
  flex-wrap: wrap;
  justify-content: center;
}

.hour-select {
  padding: 4px 6px;
  font-size: 14px;
  border-radius: 4px;
  border: 1px solid #ccc;
  min-width: 85px;
}

.add-btn {
  background-color: #4caf50;
  color: white;
  border: none;
  padding: 4px 8px;
  cursor: pointer;
  border-radius: 4px;
  font-size: 14px;
}

.add-btn:hover {
  background-color: #388e3c;
}

.delete-btn {
  background-color: #e53935;
  color: white;
  border: none;
  padding: 4px 8px;
  cursor: pointer;
  border-radius: 4px;
  font-size: 14px;
}

.delete-btn:hover {
  background-color: #c62828;
}

@media (max-width: 600px) {
  .franja {
    flex-direction: row;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 6px;
  }

  .hour-select {
    font-size: 13px;
    min-width: 70px;
  }

  .add-btn,
  .delete-btn {
    font-size: 13px;
    padding: 4px 6px;
  }

  .horaris-container {
    padding: 0 12px;
  }
}
</style>
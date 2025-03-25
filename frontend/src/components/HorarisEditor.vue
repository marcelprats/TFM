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
            <input type="checkbox" v-model="horari.tancat" @change="toggleTancat(horari)" />
          </td>
          <td>
            <div v-if="horari.tancat">🔒 Tancat</div>
            <div v-else>
              <div
                v-for="(franja, i) in horari.franjes"
                :key="i"
                class="franja"
              >
                <select v-model="franja.obertura" class="hour-select">
                  <option v-for="h in hores" :key="'o' + h" :value="h">{{ h }}</option>
                </select>
                <span>-</span>
                <select v-model="franja.tancament" class="hour-select">
                  <option v-for="h in hores" :key="'t' + h" :value="h">{{ h }}</option>
                </select>
                <button @click="eliminarFranja(horari, i)" class="delete-btn">❌</button>
              </div>
              <button @click="afegirFranja(horari)" class="add-btn">➕ Afegir franja</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
const props = defineProps({
  horaris: { type: Array, required: true }
});

const hores = Array.from({ length: 24 * 4 }, (_, i) => {
  const h = Math.floor(i / 4).toString().padStart(2, "0");
  const m = (i % 4 * 15).toString().padStart(2, "0");
  return `${h}:${m}`;
});

const capitalize = (text) => text.charAt(0).toUpperCase() + text.slice(1);

const afegirFranja = (horari) => {
  if (!horari.franjes.length) {
    horari.franjes.push({ obertura: "09:00", tancament: "13:00" });
  } else {
    const ultimaFranja = horari.franjes[horari.franjes.length - 1];
    const novaObertura = (parseInt(ultimaFranja.tancament.split(":"), 10) + 1)
      .toString()
      .padStart(2, "0") + ":00";

    horari.franjes.push({ obertura: novaObertura, tancament: "00:00" });
  }
};

const eliminarFranja = (horari, index) => {
  horari.franjes.splice(index, 1);
};

const toggleTancat = (horari) => {
  if (horari.tancat) {
    horari.franjes = [];
  } else {
    horari.franjes.push({ obertura: "09:00", tancament: "13:00" });
  }
};
</script>

<style scoped>
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
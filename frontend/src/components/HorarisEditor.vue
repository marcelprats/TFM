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
              <div v-for="(franja, i) in horari.franjes" :key="i" class="franja">
                <input type="time" v-model="franja.obertura" />
                <span>-</span>
                <input type="time" v-model="franja.tancament" />
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

const capitalize = (text) => text.charAt(0).toUpperCase() + text.slice(1);

const afegirFranja = (horari) => {
  if (!horari.franjes.length) {
    // Primera franja sempre de 09:00 a 13:00
    horari.franjes.push({ obertura: "09:00", tancament: "13:00" });
  } else {
    // Nova franja: inicia 1h després de l'última i acaba a les 00:00
    const ultimaFranja = horari.franjes[horari.franjes.length - 1];
    const novaObertura = (parseInt(ultimaFranja.tancament.split(":")[0], 10) + 1)
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
  gap: 8px;
  margin-bottom: 5px;
  justify-content: center;
}

input[type="time"] {
  padding: 4px;
  border-radius: 5px;
  border: 1px solid #ccc;
}

.add-btn {
  background-color: #4caf50;
  color: white;
  border: none;
  padding: 4px 8px;
  cursor: pointer;
  border-radius: 4px;
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
}

.delete-btn:hover {
  background-color: #c62828;
}
</style>

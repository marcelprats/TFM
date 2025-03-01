<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";

const productes = ref<{ id: number; nom: string; preu: number }[]>([]);
const newProduct = ref({ nom: "", preu: 0 });
const errorMessage = ref("");

const fetchProductes = async () => {
  try {
    const response = await axios.get("http://127.0.0.1:8000/api/productes");
    productes.value = response.data;
  } catch (error) {
    console.error("Error carregant productes:", error);
  }
};

const addProducte = async () => {
  try {
    await axios.post("http://127.0.0.1:8000/api/productes", newProduct.value);
    newProduct.value = { nom: "", preu: 0 };
    fetchProductes();
  } catch (error) {
    errorMessage.value = "Error afegint producte.";
  }
};

const deleteProducte = async (id: number) => {
  try {
    await axios.delete(`http://127.0.0.1:8000/api/productes/${id}`);
    fetchProductes();
  } catch (error) {
    errorMessage.value = "Error eliminant producte.";
  }
};

onMounted(fetchProductes);
</script>

<template>
  <div>
    <h1>Gestió de Productes</h1>
    <div>
      <input v-model="newProduct.nom" placeholder="Nom del producte" />
      <input v-model="newProduct.preu" type="number" placeholder="Preu" />
      <button @click="addProducte">Afegir Producte</button>
    </div>
    <ul>
      <li v-for="product in productes" :key="product.id">
        {{ product.nom }} - {{ product.preu }}€
        <button @click="deleteProducte(product.id)">Eliminar</button>
      </li>
    </ul>
  </div>
</template>

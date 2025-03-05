<script setup lang="ts">
import { useRouter } from "vue-router";
import { ref, onMounted } from "vue";

const router = useRouter();
const productImage = ref("");
const storeImage = ref("");

// Funció per comprovar si una imatge existeix
const imageExists = (url: string) => {
  return new Promise((resolve) => {
    const img = new Image();
    img.src = url;
    img.onload = () => resolve(true);
    img.onerror = () => resolve(false);
  });
};

// Funció per obtenir la imatge del producte
const getImageUrl = async (productId: number) => {
  const imagePath = new URL(`/img/img_${productId}.jpg`, import.meta.url).href;
  return (await imageExists(imagePath)) ? imagePath : new URL(`/img/no-imatge.jpg`, import.meta.url).href;
};

// Funció per obtenir la imatge de botigues
const getImagePath = async (imageName: string) => {
  const imagePath = new URL(`/img/${imageName}`, import.meta.url).href;
  return (await imageExists(imagePath)) ? imagePath : new URL("/img/no-imatge.jpg", import.meta.url).href;
};

// Assignem les imatges correctament quan es carrega la pàgina
onMounted(async () => {
  productImage.value = await getImageUrl(1);
  storeImage.value = await getImagePath("botigues.png");
});

const goTo = (path: string) => {
  router.push(path);
};
</script>

<template>
  <div class="area-personal">
    <h1>Benvingut a l'Àrea Personal</h1>
    <p>Aquí pots gestionar els teus productes i botigues. Escull una opció per continuar:</p>

    <div class="options-container">
      <div class="option-card" @click="goTo('/area-personal-botigues')">
        <img :src="storeImage" alt="Botigues" class="product-image" />
        <h2>Gestiona les teves botigues</h2>
        <p>Configura i administra les teves botigues.</p>
      </div>
      
      <div class="option-card" @click="goTo('/area-personal-productes')">
        <img :src="productImage" alt="Producte" class="product-image" />
        <h2>Gestiona els teus productes</h2>
        <p>Afegeix, edita i elimina productes fàcilment.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.area-personal {
  text-align: center;
  margin-top: 50px;
  padding: 20px;
}

.options-container {
  display: flex;
  justify-content: center;
  gap: 30px;
  margin-top: 30px;
}

.option-card {
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  width: 300px;
  text-align: center;
  cursor: pointer;
  transition: transform 0.2s ease-in-out;
}

.option-card:hover {
  transform: scale(1.05);
}

.option-card img {
  width: 100px;
  height: 100px;
  margin-bottom: 10px;
  object-fit: cover;
}

.option-card h2 {
  font-size: 20px;
  margin-bottom: 10px;
}

.option-card p {
  font-size: 14px;
  color: #666;
}
</style>

// src/views/UserProfile.vue
<template>
  <div class="profile-page">
    <aside class="sidebar">
      <nav>
        <ul>
          <li
            :class="{ active: selectedMenu === 'profile' }"
            @click="selectedMenu = 'profile'"
          >
            Dades Usuari
          </li>
          <li
            :class="{ active: selectedMenu === 'orders' }"
            @click="selectedMenu = 'orders'"
          >
            Historial de Comandes
          </li>
          <li
            :class="{ active: selectedMenu === 'reviews' }"
            @click="selectedMenu = 'reviews'"
          >
            Valoracions
          </li>
        </ul>
      </nav>
    </aside>
    <main class="content">
      <UserDetails   v-if="selectedMenu === 'profile'" :user="user" :role="role" />
      <OrderHistory  v-else-if="selectedMenu === 'orders'" />
      <ProductReviews v-else-if="selectedMenu === 'reviews'" />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { getUser, getUserType } from '../services/authService';
import UserDetails    from '../components/UserDetails.vue';
import OrderHistory   from '../components/OrderHistory.vue';
import ProductReviews from '../components/ProductReviews.vue';

type Menu = 'profile' | 'orders' | 'reviews';
const selectedMenu = ref<Menu>('profile');
const user = ref<{ name: string; email: string } | null>(null);
const role = ref('');

onMounted(() => {
  user.value = getUser();
  role.value = getUserType() === 'vendor' ? 'Venedor' : 'Comprador';
});
</script>


<style scoped>
/* Estils del layout general (sidebar + content) */
.profile-page {
  display: flex;
  max-width: 1200px;
  margin: 40px auto;
  gap: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #fff;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}
.sidebar {
  width: 250px;
  border-right: 1px solid #ddd;
  padding: 20px;
  background-color: #f8f8f8;
}
.sidebar ul {
  list-style: none;
  padding: 0;
}
.sidebar li {
  padding: 10px;
  margin-bottom: 10px;
  cursor: pointer;
  border-radius: 4px;
  transition: background-color 0.3s;
}

.sidebar ul li {
  padding: 12px;
  font-weight: 500;
}
.sidebar ul li.active {
  background-color: #1e7e34;
  color: #fff;
}
.sidebar li:hover {
  background-color: #28a745;
  color: #fff;
}
.content {
  flex: 1;
  padding: 20px;
}
@media (max-width: 768px) {
  .profile-page {
    flex-direction: column;
    margin: 0 20px;
  }
  .sidebar {
    width: 90%;
    border-right: none;
    border-bottom: 1px solid #ddd;
  }
  .content {
    padding: 10px;
  }
}
</style>

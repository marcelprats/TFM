<template>
  <div>
    <component :is="headerComponent" class="header-container" />
    <main class="content">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import HomeHeader from "../components/HomeHeader.vue";
import MainHeader from "../components/MainHeader.vue";

const route = useRoute();
const headerComponent = computed(() => (route.path === "/" ? HomeHeader : MainHeader));
</script>

<style scoped>
.content {
  margin-top: 100px;
  padding: 20px;
}
</style>

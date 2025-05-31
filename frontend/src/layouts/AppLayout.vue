<template>
  <div>
    <!-- Header -->
    <component
      :is="headerComponent"
      class="header-container"
    />

    <!-- Contingut principal -->
    <main class="content">
      <router-view/>
    </main>

    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import PreHomeHeader from '../components/PreHomeHeader.vue'
import HomeHeader    from '../components/HomeHeader.vue'
import MainHeader    from '../components/MainHeader.vue'
import Footer        from '../components/Footer.vue'

const route = useRoute()

const headerComponent = computed(() => {
  // Accedeix explícitament a route.path perquè sigui reactiu
  const path = route.path
  if (path === '/') return PreHomeHeader
  if (path === '/home') return HomeHeader
  return MainHeader
})
</script>

<style scoped>
.header-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 100;
}
/* deixa espai per l’estat fixat del header */
.content {
  margin-top: 64px;
  padding: 0;
  min-height: calc(100vh - 64px - /* footer height */ 120px);
}
</style>

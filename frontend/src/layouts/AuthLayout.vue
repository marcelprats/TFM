<template>
  <div class="auth-container">
    <div class="auth-box">
      <h2>{{ title }}</h2>
      <slot></slot>
      <p class="auth-link">
        <slot name="switch-link"></slot>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ title: string }>();
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: calc(100vh - 80px);
  background: white;
}

.auth-box {
  background: #ffffff;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
  width: 350px;
}

h2 {
  margin-bottom: 20px;
  color: #2c3e50;
  font-size: 22px;
}

.auth-link {
  margin-top: 15px;
  font-size: 14px;
}
</style>

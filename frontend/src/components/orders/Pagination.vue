<template>
  <div class="pagination">
    <button @click="emit('prev')" :disabled="current === 1">Anterior</button>
    <span>Pàgina {{ current }} de {{ total }}</span>
    <button @click="emit('next')" :disabled="current === total">Següent</button>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'

const props = defineProps<{
  current: number
  total: number
}>()

const emit = defineEmits<{
  (e: 'prev'): void
  (e: 'next'): void
}>()
</script>

<style scoped>
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
}

.pagination button {
  padding: 8px 14px;
  border: none;
  border-radius: 4px;
  background-color: #28a745;
  color: #fff;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.pagination button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.pagination span {
  font-size: 0.9rem;
}
</style>

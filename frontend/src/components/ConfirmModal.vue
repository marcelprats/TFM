<template>
  <div
    class="modal-overlay"
    @click.self="onCancel"
    tabindex="-1"
    @keydown.esc.prevent="onCancel"
    role="dialog"
    aria-modal="true"
  >
    <div class="modal-content">
      <h2 class="modal-title">{{ title }}</h2>
      <ul class="modal-list">
        <li v-for="item in items" :key="item.id">
          {{ item.product.nom }} <span v-if="item.quantity">(x{{ item.quantity }})</span>
        </li>
      </ul>
      <div class="modal-actions">
        <button class="btn confirm-btn" @click="onConfirm">
          {{ confirmText }}
        </button>
        <button class="btn cancel-btn" @click="onCancel">
          Cancel·lar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits, onMounted, onBeforeUnmount, ref } from 'vue';

interface CartItem {
  id: number;
  product: { nom: string };
  quantity?: number;
}

const props = defineProps<{
  title: string;
  items: CartItem[];
  confirmText?: string;
}>();

const emit = defineEmits<{
  (e: 'confirm'): void;
  (e: 'cancel'): void;
}>();

const confirmText = props.confirmText || 'Confirmar';

function onConfirm() {
  emit('confirm');
}

function onCancel() {
  emit('cancel');
}

// Per fer focus dins el modal i captar Esc
const overlay = ref<HTMLElement | null>(null);
onMounted(() => {
  overlay.value?.focus();
});
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  outline: none;
  z-index: 1000;
}
.modal-content {
  background: #fff;
  padding: 1.5rem;
  border-radius: 8px;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.modal-title {
  margin-top: 0;
  font-size: 1.25rem;
  color: #333;
}
.modal-list {
  margin: 1rem 0;
  max-height: 200px;
  overflow-y: auto;
  padding-left: 1.2rem;
}
.modal-list li {
  margin-bottom: 0.5rem;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
.confirm-btn {
  background-color: #28a745;
}
.confirm-btn:hover {
  background-color: #218838;
}
.cancel-btn {
  background-color: #d9534f;
}
.cancel-btn:hover {
  background-color: #c9302c;
}
.btn {
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}
</style>

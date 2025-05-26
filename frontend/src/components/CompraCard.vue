<template>
  <div class="p-4 rounded-lg shadow hover:shadow-lg transition bg-white">
    <h4 class="font-semibold">{{ purchase.product_name }}</h4>
    <p class="text-sm text-gray-500">Quantitat: {{ purchase.quantity }}</p>
    <p class="text-sm text-gray-500">
      Data: {{ formatDate(purchase.created_at) }}
    </p>
    <p class="mt-1 font-bold">Total: €{{ purchase.total.toFixed(2) }}</p>
  </div>
</template>

<script setup lang="ts">
interface Purchase {
  id: number;
  product_name: string;
  quantity: number;
  total: number;
  created_at: string;
}
const props = defineProps<{ purchase: Purchase }>();

function formatDate(dateStr: string) {
  return new Intl.DateTimeFormat('es-ES', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date(dateStr));
}
</script>

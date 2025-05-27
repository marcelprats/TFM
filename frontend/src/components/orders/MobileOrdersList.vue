<template>
  <div class="mobile-list">
    <div
      class="order-card"
      v-for="order in orders"
      :key="order.id"
    >
      <div class="card-row">
        <div class="order-summary-col">
          <span class="badge" :class="badgeClass(order.status)">
            {{ order.status }}
          </span>
          <p><strong>Codi:</strong> {{ order.order_number }}</p>
          <p><strong>Total:</strong> {{ formatPrice(order.total_amount) }}</p>
          <p><strong>Data:</strong> {{ new Date(order.created_at).toLocaleDateString() }}</p>
        </div>
        <div class="order-products-col">
          <div class="product-grid">
            <template v-if="getReserveItems(order).length">
              <div
                class="product-card"
                v-for="(item, i) in getLimitedReserveItems(order)"
                :key="i"
              >
                {{ item.product.nom }}
              </div>
              <div
                v-if="getReserveItems(order).length > 2"
                class="more-products"
              >
                +{{ getReserveItems(order).length - 2 }} més
              </div>
            </template>
            <template v-else>
              <p class="no-products">No hi ha productes.</p>
            </template>
          </div>
        </div>
      </div>
      <div class="order-actions">
        <button @click="$emit('view-summary', order.id)" class="action-btn large">Resum</button>
        <button @click="$emit('view-ticket', order.id)"  class="action-btn large">Tiquet</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';

defineProps<{ orders: any[] }>();
const emit = defineEmits<{
  (e: 'view-summary', id: number): void;
  (e: 'view-ticket',  id: number): void;
}>();

function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  return isNaN(p) ? 'No disponible' : p.toFixed(2) + ' €';
}
function getReserveItems(order: any) {
  return order.reserve?.reserve_items || [];
}
function getLimitedReserveItems(order: any) {
  return getReserveItems(order).slice(0, 2);
}
function badgeClass(status: string) {
  switch (status) {
    case 'pending':   return 'badge-pending';
    case 'reserved':  return 'badge-reserved';
    case 'completed': return 'badge-completed';
    case 'cancelled': return 'badge-cancelled';
    default:          return '';
  }
}
</script>

<style scoped>
.mobile-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.order-card {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 16px;
  transition: box-shadow 0.2s;
}

.order-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.card-row {
  display: flex;
  gap: 16px;
  border-bottom: 1px solid #eee;
  padding-bottom: 12px;
  margin-bottom: 12px;
}

.order-summary-col {
  flex: 1;
}

.order-products-col {
  flex: 2;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 8px;
}

.product-card {
  background: #f5f5f5;
  border-radius: 4px;
  padding: 6px;
  font-size: 0.85rem;
  text-align: center;
}

.more-products {
  font-size: 0.8rem;
  color: #555;
  text-align: center;
}

.order-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-top: 12px;
}

.action-btn {
  padding: 8px 14px;
  border: none;
  border-radius: 4px;
  background-color: #28a745;
  color: #fff;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.action-btn:hover {
  background-color: #218838;
}

.badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  color: #fff;
  text-transform: capitalize;
}

.badge-pending   { background-color: #ffc107; }
.badge-reserved  { background-color: #17a2b8; }
.badge-completed { background-color: #28a745; }
.badge-cancelled { background-color: #dc3545; }
</style>


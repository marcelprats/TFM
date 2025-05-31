<template>
  <div class="mobile-list">
    <div
      class="order-card"
      v-for="order in props.orders"
      :key="order.id"
    >
      <div class="card-row">
        <div class="order-summary-col">
          <span class="badge" :class="badgeClass(order.status)">
            {{ order.status }}
          </span>
          <p><strong>Codi:</strong> {{ order.order_number }}</p>
          <p><strong>Total:</strong> {{ formatPrice(order.total_amount) }}</p>
          <p><strong>Data:</strong> {{ new Date(order.created_at).toLocaleDateString('ca-ES') }}</p>
        </div>
        <div class="order-products-col">
          <div class="product-grid">
            <template v-if="reserveItems(order).length">
              <div
                class="product-card"
                v-for="(item, i) in limitedReserveItems(order)"
                :key="i"
              >
                {{ item.product.nom }}
              </div>
              <div
                v-if="reserveItems(order).length > 2"
                class="more-products"
              >
                +{{ reserveItems(order).length - 2 }} més
              </div>
            </template>
            <template v-else>
              <p class="no-products">No hi ha productes.</p>
            </template>
          </div>
        </div>
      </div>
      <div class="order-actions">
        <button @click="emit('view-summary', order.id)" class="action-btn large">Resum</button>
        <button @click="emit('view-ticket', order.id)"  class="action-btn large">Tiquet</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'

const props = defineProps<{
  orders: Array<{
    id: number
    status: string
    order_number: string
    total_amount: number | string
    created_at: string
    reserve?: { reserve_items: any[] }
  }>
}>()

const emit = defineEmits<{
  (e: 'view-summary', id: number): void
  (e: 'view-ticket',  id: number): void
}>()

function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price)
  return isNaN(p) ? 'No disponible' : p.toFixed(2) + ' €'
}

function reserveItems(order: typeof props.orders[0]) {
  return order.reserve?.reserve_items ?? []
}

function limitedReserveItems(order: typeof props.orders[0]) {
  return reserveItems(order).slice(0, 2)
}

function badgeClass(status: string) {
  switch (status) {
    case 'pending':   return 'badge-pending'
    case 'reserved':  return 'badge-reserved'
    case 'completed': return 'badge-completed'
    case 'cancelled': return 'badge-cancelled'
    default:          return ''
  }
}
</script>

<style scoped>
.mobile-list {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.order-card {
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 18px 16px 12px 16px;
  transition: box-shadow 0.2s;
}
.order-card:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.07);
}
.card-row {
  display: flex;
  gap: 16px;
  border-bottom: 1px solid #eee;
  padding-bottom: 10px;
  margin-bottom: 10px;
}
.order-summary-col { flex: 1; }
.order-products-col { flex: 2; }
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 8px;
}
.product-card {
  background: #eaf9f2;
  border-radius: 5px;
  padding: 7px 2px;
  font-size: 0.97rem;
  text-align: center;
  color: #212c3a;
  font-weight: 500;
}
.more-products {
  font-size: 0.84rem;
  color: #555;
  text-align: center;
  font-style: italic;
  opacity: 0.8;
}
.no-products {
  color: #888;
  font-size: 0.91rem;
  text-align: center;
}
.order-actions {
  display: flex;
  justify-content: center;
  gap: 14px;
  margin-top: 10px;
}
.action-btn {
  padding: 9px 16px;
  border: none;
  border-radius: 5px;
  background-color: #28a745;
  color: #fff;
  font-size: 1.01rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}
.action-btn:hover {
  background-color: #218838;
}
.badge {
  padding: 4px 10px;
  border-radius: 5px;
  font-size: 0.85rem;
  color: #fff;
  text-transform: capitalize;
  margin-bottom: 4px;
  display: inline-block;
}
.badge-pending   { background-color: #ffc107; color: #333; }
.badge-reserved  { background-color: #17a2b8; }
.badge-completed { background-color: #28a745; }
.badge-cancelled { background-color: #dc3545; }
</style>


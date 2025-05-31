<template>
  <table class="orders-table">
    <thead>
      <tr>
        <th @click="$emit('change-sort', 'order_number')">
          Codi <span v-if="sortField==='order_number'">{{ dirArrow }}</span>
        </th>
        <th @click="$emit('change-sort', 'total_amount')">
          Total <span v-if="sortField==='total_amount'">{{ dirArrow }}</span>
        </th>
        <th @click="$emit('change-sort', 'payment_method')">
          Pagament <span v-if="sortField==='payment_method'">{{ dirArrow }}</span>
        </th>
        <th @click="$emit('change-sort', 'status')">
          Estat <span v-if="sortField==='status'">{{ dirArrow }}</span>
        </th>
        <th @click="$emit('change-sort', 'created_at')">
          Data <span v-if="sortField==='created_at'">{{ dirArrow }}</span>
        </th>
        <th>Accions</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="order in orders" :key="order.id">
        <td>{{ order.order_number }}</td>
        <td>{{ formatPrice(order.total_amount) }}</td>
        <td>{{ order.payment_method }}</td>
        <td>
          <span class="badge" :class="badgeClass(order.status)">
            {{ order.status }}
          </span>
        </td>
        <td>{{ new Date(order.created_at).toLocaleString() }}</td>
        <td>
          <button @click="$emit('view-summary', order.id)" class="action-btn large">Resum</button>
          <button @click="$emit('view-ticket',  order.id)" class="action-btn large">Tiquet</button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script setup lang="ts">
import { defineProps, defineEmits, computed } from 'vue';

const props = defineProps<{
  orders: any[];
  sortField: string;
  sortDirection: 'asc'|'desc';
}>();
const emit = defineEmits<{
  (e: 'change-sort', field: string): void;
  (e: 'view-summary', id: number): void;
  (e: 'view-ticket',  id: number): void;
}>();

const dirArrow = computed(() =>
  props.sortDirection === 'asc' ? '↑' : '↓'
);

function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  return isNaN(p) ? 'No disponible' : p.toFixed(2) + ' €';
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
.orders-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 16px;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
}
.orders-table th,
.orders-table td {
  border: 1px solid #e0e0e0;
  padding: 12px 14px;
  text-align: center;
  font-size: 1.03rem;
}
.orders-table th {
  background-color: #f7f7f7;
  cursor: pointer;
  user-select: none;
  font-weight: 700;
  color: #222;
}
.orders-table th span {
  margin-left: 6px;
  font-size: 0.92rem;
}
.action-btn {
  padding: 7px 13px;
  border: none;
  border-radius: 4px;
  background-color: #28a745;
  color: #fff;
  font-size: 1.01rem;
  font-weight: 600;
  cursor: pointer;
  margin: 3px !important;
  transition: background-color 0.2s;
}
.action-btn:hover {
  background-color: #218838;
}
.badge {
  padding: 5px 11px;
  border-radius: 6px;
  font-size: 0.90rem;
  color: #fff;
  text-transform: capitalize;
  font-weight: 500;
}
.badge-pending   { background-color: #ffc107; color: #333; }
.badge-reserved  { background-color: #17a2b8; }
.badge-completed { background-color: #28a745; }
.badge-cancelled { background-color: #dc3545; }
</style>

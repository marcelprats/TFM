<template>
  <div class="filters">
    <input
      type="text"
      :value="searchQuery"
      @input="e => $emit('update:searchQuery', e.target.value)"
      placeholder="Cerca per codi de comanda..."
      class="filter-input"
    />
    <select
      :value="paymentMethod"
      @change="e => $emit('update:paymentMethod', e.target.value)"
      class="filter-select"
    >
      <option value="">Tots els mètodes</option>
      <option value="online">Online</option>
      <option value="cash">Cash</option>
    </select>
    <select
      :value="status"
      @change="e => $emit('update:status', e.target.value)"
      class="filter-select"
    >
      <option value="">Tots els estats</option>
      <option value="pending">Pendent</option>
      <option value="reserved">Reservada</option>
      <option value="completed">Completada</option>
      <option value="cancelled">Cancel·lada</option>
    </select>
    <div class="date-filters">
      <label>
        Data Inici:
        <input
          type="date"
          :value="startDate"
          @input="e => $emit('update:startDate', e.target.value)"
          class="filter-date"
        />
      </label>
      <label>
        Data Fi:
        <input
          type="date"
          :value="endDate"
          @input="e => $emit('update:endDate', e.target.value)"
          class="filter-date"
        />
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  searchQuery: string;
  paymentMethod: string;
  status: string;
  startDate: string;
  endDate: string;
}>();
defineEmits([
  'update:searchQuery',
  'update:paymentMethod',
  'update:status',
  'update:startDate',
  'update:endDate'
]);
</script>

<style scoped>
.filters {
  margin-bottom: 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}

.filter-input,
.filter-select,
.filter-date {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 0.9rem;
  transition: border-color 0.2s;
}

.filter-input:focus,
.filter-select:focus,
.filter-date:focus {
  outline: none;
  border-color: #28a745;
}

.date-filters {
  display: flex;
  gap: 8px;
}
</style>


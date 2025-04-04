<template>
  <div class="checkout-container">
    <h1>Finalitzar Comanda</h1>
    <div class="checkout-summary">
      <p>Estàs a punt de reservar els teus productes.</p>
      <p>
        Pagament en línia: <strong>10% del total</strong>
      </p>
      <p>
        La resta es pagarà al local.
      </p>
      <div class="deposit-info">
        <p><strong>Total Reservat:</strong> {{ formatPrice(reserve.total_reserved) }}</p>
        <p><strong>Deposit (10%):</strong> {{ formatPrice(reserve.deposit_amount) }}</p>
      </div>
    </div>
    <div class="terms">
      <input type="checkbox" id="acceptConditions" v-model="acceptedConditions" />
      <label for="acceptConditions">
        Accepto les condicions de reserva
      </label>
    </div>
    <button
      class="btn checkout-btn"
      :disabled="!acceptedConditions"
      @click="handleCheckout"
    >
      Pagar Deposit i Confirmar Reserva
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const API_URL = 'http://127.0.0.1:8000/api';

// Simulem que tenim la reserva creada (per exemple, després d'afegir els productes al carret i convertir-lo a reserva)
const reserve = ref({
  id: 1,
  total_reserved: 200.00, // Aquest valor es calcularia sumant els productes reservats
  deposit_amount: 20.00,  // Deposit = 10% del total_reserved
  status: 'pending',
});

// Checkbox per acceptar condicions
const acceptedConditions = ref(false);

// Funció per formatar preus
function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  if (isNaN(p)) return 'No disponible';
  return p.toFixed(2) + ' €';
}

// Funció per finalitzar el checkout (pagar el deposit i confirmar la reserva)
// Aquí simulem una crida API que crearia l'ordre de pagament.
async function handleCheckout() {
  if (!acceptedConditions.value) {
    alert('Has d’acceptar les condicions de reserva.');
    return;
  }
  try {
    const token = localStorage.getItem('userToken');
    // Aquesta crida API és un exemple. En el teu back-end hauràs d'implementar la lògica de pagament del deposit
    const response = await axios.post(
      `${API_URL}/orders`,
      {
        reserve_id: reserve.value.id,
        total_amount: reserve.value.total_reserved,
        payment_method: 'online', // Aquí s'especifica el mètode de pagament
        transaction_id: 'fake-transaction-id', // En un entorn real, aquí vindria la resposta del pagament
        status: 'pending', // O 'paid' si el pagament es confirma
      },
      {
        headers: { Authorization: `Bearer ${token}` },
      }
    );
    alert(response.data.message);
    // Un cop confirmada la comanda, pots redirigir a una pàgina de confirmació
    router.push('/order-confirmation');
  } catch (error: any) {
    console.error('Error finalitzant la comanda:', error);
    alert('Error finalitzant la comanda. Si us plau, intenta-ho més tard.');
  }
}
</script>

<style scoped>
.checkout-container {
  max-width: 600px;
  margin: 30px auto;
  padding: 20px;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  text-align: center;
}

.checkout-summary {
  margin-bottom: 20px;
}

.deposit-info {
  margin: 10px 0;
}

.terms {
  margin: 20px 0;
  text-align: left;
}

.btn.checkout-btn {
  background-color: #28a745;
  color: #fff;
  padding: 12px 20px;
  font-size: 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.btn.checkout-btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.btn.checkout-btn:hover:enabled {
  background-color: #218838;
}
</style>
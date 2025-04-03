import { defineStore } from 'pinia';

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: []  // Cada ítem: { product, quantity }
  }),
  getters: {
    totalItems: (state) => state.items.reduce((acc, item) => acc + item.quantity, 0),
    totalPrice: (state) =>
      state.items.reduce((acc, item) => acc + item.product.preu * item.quantity, 0)
  },
  actions: {
    addItem(product, quantity = 1) {
      const existing = this.items.find(item => item.product.id === product.id);
      if (existing) {
        existing.quantity += quantity;
      } else {
        this.items.push({ product, quantity });
      }
    },
    updateItem(productId, quantity) {
      const item = this.items.find(item => item.product.id === productId);
      if (item) {
        item.quantity = quantity;
      }
    },
    removeItem(productId) {
      this.items = this.items.filter(item => item.product.id !== productId);
    },
    clearCart() {
      this.items = [];
    }
  }
});

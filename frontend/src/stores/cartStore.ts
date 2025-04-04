// stores/cartStore.ts
import { defineStore } from 'pinia';

interface CartItem {
  id: number;
  product: any; // o defineix una interfície per al producte
  quantity: number;
  reserved_price: number;
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [] as CartItem[],
    nextId: 1, // Per assignar un ID únic als ítems del carret
  }),
  getters: {
    totalPrice: (state) =>
      state.items.reduce((total, item) => total + (item.quantity * item.reserved_price), 0),
  },
  actions: {
    addItem(product: any, quantity: number) {
      // Suposem que 'reserved_price' és el preu actual del producte
      const reserved_price = parseFloat(product.preu);
      // Comprovem si el producte ja existeix al carret
      const existingItem = this.items.find((item) => item.product.id === product.id);
      if (existingItem) {
        existingItem.quantity += quantity;
      } else {
        this.items.push({
          id: this.nextId++,
          product: product,
          quantity: quantity,
          reserved_price: reserved_price,
        });
      }
    },
    // Altres funcions per eliminar, actualitzar, etc.
  },
});

import { defineStore } from 'pinia'
import axios from 'axios'

export interface CartItem {
  id: number
  quantity: number
  reserved_price: number
  product: {
    id: number
    nom: string
    preu: number
  }
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [] as CartItem[]
  }),
  getters: {
    itemCount: (state) =>
      state.items.reduce((sum, i) => sum + i.quantity, 0),
    totalPrice: (state) =>
      state.items.reduce((sum, i) => sum + i.quantity * i.reserved_price, 0)
  },
  actions: {
    async fetchCart() {
      // Si no hi ha token, netegem directament
      if (!localStorage.getItem('userToken')) {
        this.items = []
        return
      }
      try {
        // Sobreescrivim ja no cal posar baseURL ni headers
        const { data } = await axios.get<{ cart_items: CartItem[] }>('/cart')
        this.items = data.cart_items
      } catch {
        this.items = []
      }
    },

    async addItem(productId: number, quantity: number) {
      if (!localStorage.getItem('userToken')) {
        throw new Error('No token')
      }
      await axios.post('/cart', { product_id: productId, quantity })
      await this.fetchCart()
    },

    async removeItem(itemId: number) {
      if (!localStorage.getItem('userToken')) {
        throw new Error('No token')
      }
      await axios.delete(`/cart/${itemId}`)
      await this.fetchCart()
    }
  }
})

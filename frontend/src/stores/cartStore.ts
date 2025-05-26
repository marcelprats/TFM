// src/stores/cartStore.ts
import { defineStore } from 'pinia'
import axios from 'axios'

const API_URL = 'http://127.0.0.1:8000/api'

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
    itemCount: (state) => state.items.reduce((sum, i) => sum + i.quantity, 0),
    totalPrice: (state) => state.items.reduce((sum, i) => sum + i.quantity * i.reserved_price, 0)
  },
  actions: {
    async fetchCart() {
      const token = localStorage.getItem('userToken')
      if (!token) {
        this.items = []
        return
      }
      try {
        const { data } = await axios.get<{ cart_items: CartItem[] }>(
          `${API_URL}/cart`,
          { headers: { Authorization: `Bearer ${token}` } }
        )
        this.items = data.cart_items
      } catch {
        this.items = []
      }
    },
    async addItem(productId: number, quantity: number) {
      const token = localStorage.getItem('userToken')
      if (!token) throw new Error('No token')
      await axios.post(
        `${API_URL}/cart`,
        { product_id: productId, quantity },
        { headers: { Authorization: `Bearer ${token}` } }
      )
      await this.fetchCart()
    },
    async removeItem(itemId: number) {
      const token = localStorage.getItem('userToken')
      if (!token) throw new Error('No token')
      await axios.delete(
        `${API_URL}/cart/${itemId}`,
        { headers: { Authorization: `Bearer ${token}` } }
      )
      await this.fetchCart()
    }
  }
})

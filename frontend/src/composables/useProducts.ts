import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

export interface Product {
  id: number;
  nom: string;
  preu: string;
  stock: number;
  imatge: string | null;
  categoria: number;
  subcategoria: number;
  botiga_id?: number;
  botiga?: { id: number; nom: string };
}

export interface Category {
  id: number;
  nom: string;
  parent_id: number | null;
}

export function useProducts() {
  const products = ref<Product[]>([])
  const loading = ref(true)
  const error = ref<string|null>(null)

  const categories = ref<Category[]>([])
  const subcategories = ref<Category[]>([])
  const filteredSubcategories = ref<Category[]>([])

  const selectedCategories = ref<Category[]>([])
  const selectedSubcategories = ref<Category[]>([])
  const searchQuery = ref('')
  const filterByStock = ref(false)

  const minPrice = ref(0)
  const maxPrice = ref(0)
  const selectedMinPrice = ref(0)
  const selectedMaxPrice = ref(0)

  // Debounced search
  const debouncedQuery = ref('')
  let debounceTimer: ReturnType<typeof setTimeout>
  watch(searchQuery, q => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      debouncedQuery.value = q
    }, 300)
  })

  onMounted(async () => {
    try {
      loading.value = true
      const [prodRes, catRes] = await Promise.all([
        axios.get<Product[]>('/productes'),
        axios.get<Category[]>('/categories'),
      ])
      products.value = prodRes.data
      const allCats = catRes.data
      categories.value = allCats.filter(c => c.parent_id === null)
      subcategories.value = allCats.filter(c => c.parent_id !== null)

      if (products.value.length) {
        const prices = products.value.map(p => parseFloat(p.preu))
        minPrice.value = Math.min(...prices)
        maxPrice.value = Math.max(...prices)
        selectedMinPrice.value = minPrice.value
        selectedMaxPrice.value = maxPrice.value
      }
    } catch (e: any) {
      error.value = e.message || 'Error carregant dades'
    } finally {
      loading.value = false
    }
  })

  watch(selectedCategories, cats => {
    const ids = cats.map(c => c.id)
    filteredSubcategories.value = subcategories.value.filter(
      s => s.parent_id !== null && ids.includes(s.parent_id!)
    )
    selectedSubcategories.value = selectedSubcategories.value.filter(s =>
      filteredSubcategories.value.some(f => f.id === s.id)
    )
  })

  const filteredProducts = computed(() =>
    products.value.filter(p => {
      const nameMatch = p.nom
        .toLowerCase()
        .includes(debouncedQuery.value.toLowerCase())
      const price = parseFloat(p.preu)
      const priceMatch =
        price >= selectedMinPrice.value && price <= selectedMaxPrice.value
      const stockMatch = !filterByStock.value || p.stock > 0
      const categoryMatch =
        !selectedCategories.value.length ||
        selectedCategories.value.some(c => c.id === p.categoria)
      const subcategoryMatch =
        !selectedSubcategories.value.length ||
        selectedSubcategories.value.some(s => s.id === p.subcategoria)

      return (
        nameMatch && priceMatch && stockMatch && categoryMatch && subcategoryMatch
      )
    })
  )

  return {
    products,
    loading,
    error,
    categories,
    filteredSubcategories,
    selectedCategories,
    selectedSubcategories,
    searchQuery,
    filterByStock,
    minPrice,
    maxPrice,
    selectedMinPrice,
    selectedMaxPrice,
    filteredProducts,
    totalResults: computed(() => filteredProducts.value.length),
    resetFilters: () => {
      selectedCategories.value = []
      selectedSubcategories.value = []
      searchQuery.value = ''
      filterByStock.value = false
      selectedMinPrice.value = minPrice.value
      selectedMaxPrice.value = maxPrice.value
    },
  }
}

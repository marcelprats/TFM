import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';

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
  const products = ref<Product[]>([]);
  const loading = ref(true);
  const error = ref<string | null>(null);

  const categories = ref<Category[]>([]);
  const subcategories = ref<Category[]>([]);
  const filteredSubcategories = ref<Category[]>([]);

  const selectedCategories = ref<Category[]>([]);
  const selectedSubcategories = ref<Category[]>([]);
  const searchQuery = ref('');
  const filterByStock = ref(false);

  const minPrice = ref(0);
  const maxPrice = ref(0);
  const selectedMinPrice = ref(0);
  const selectedMaxPrice = ref(0);

  // Debounced search query
  const debouncedQuery = ref('');
  let debounceTimer: ReturnType<typeof setTimeout>;
  watch(searchQuery, (q) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      debouncedQuery.value = q;
    }, 300);
  });

  onMounted(async () => {
    try {
      loading.value = true;
      const [prodRes, catRes] = await Promise.all([
        axios.get<Product[]>('/productes'),
        axios.get<Category[]>('/categories'),
      ]);
      products.value = prodRes.data;
      const allCats = catRes.data;
      categories.value = allCats.filter(c => c.parent_id === null);
      subcategories.value = allCats.filter(c => c.parent_id !== null);

      if (products.value.length) {
        const prices = products.value.map(p => parseFloat(p.preu));
        minPrice.value = Math.min(...prices);
        maxPrice.value = Math.max(...prices);
        selectedMinPrice.value = minPrice.value;
        selectedMaxPrice.value = maxPrice.value;
      }
    } catch (e: any) {
      error.value = e.message || 'Error carregant dades';
    } finally {
      loading.value = false;
    }
  });

  // Update subcategories when categories change
    watch(selectedCategories, (cats) => {
    const ids = cats.map(c => c.id)
    filteredSubcategories.value = subcategories.value.filter(s => {
        const pid = s.parent_id
        return pid !== null && ids.includes(pid)
    })
    // Eliminar subcategories prèviament seleccionades que ja no siguin vàlides
    selectedSubcategories.value = selectedSubcategories.value.filter(s =>
        filteredSubcategories.value.some(f => f.id === s.id)
    )
    })


  const filteredProducts = computed(() =>
    products.value.filter(p => {
      // name match with debounced query
      if (!p.nom.toLowerCase().includes(debouncedQuery.value.toLowerCase())) {
        return false;
      }
      // price filter
      const price = parseFloat(p.preu);
      if (price < selectedMinPrice.value || price > selectedMaxPrice.value) {
        return false;
      }
      // stock filter
      if (filterByStock.value && p.stock <= 0) return false;
      // category filter
      if (
        selectedCategories.value.length &&
        !selectedCategories.value.some(c => c.id === p.categoria)
      ) return false;
      // subcategory filter
      if (
        selectedSubcategories.value.length &&
        !selectedSubcategories.value.some(s => s.id === p.subcategoria)
      ) return false;
      return true;
    })
  );

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
      selectedCategories.value = [];
      selectedSubcategories.value = [];
      searchQuery.value = '';
      filterByStock.value = false;
      selectedMinPrice.value = minPrice.value;
      selectedMaxPrice.value = maxPrice.value;
    },
  };
}

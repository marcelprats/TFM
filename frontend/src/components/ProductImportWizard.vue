<template>
  <div class="wizard">
    <div class="modal">
      <div class="modal-content">
        <!-- Header -->
        <div class="header">
          <h2>{{ t('import.title') }}</h2>
          <button class="close-btn" @click="reset">✖</button>
        </div>

        <!-- Pasos -->
        <div class="steps">
          <span
            v-for="(step, index) in steps"
            :key="index"
            :class="{ active: currentStep === index + 1, clickable: currentStep > index + 1 }"
            @click="goToStep(index + 1)"
          >
            <span v-if="currentStep > index + 1">✅</span>
            {{ index + 1 }}. {{ t(step.label) }}
          </span>
        </div>

        <!-- Step 1: Botiga i fitxer -->
        <div v-if="currentStep === 1">
          <label>{{ t('import.selectStore') }}</label>
          <select v-model="form.botiga_id">
            <option disabled value="">{{ t('import.chooseStore') }}</option>
            <option v-for="b in botigues" :key="b.id" :value="b.id">{{ b.nom }}</option>
          </select>

          <label>{{ t('import.selectFile') }}</label>
          <input type="file" accept=".xlsx,.xls" @change="handleFileChange" />

          <div class="nav-buttons">
            <button disabled>{{ t('common.previous') }}</button>
            <button @click="nextStep" :disabled="!form.botiga_id || !file">{{ t('common.next') }}</button>
          </div>
        </div>

        <!-- Step 2: Mapatge de columnes -->
        <div v-else-if="currentStep === 2">
          <h4>{{ t('import.mapColumns') }}</h4>
          <div v-for="field in modelFields" :key="field" class="map-field">
            <label>{{ t('fields.' + field) }}</label>
            <select v-model="mapping[field]">
              <option value="">{{ t('import.ignore') }}</option>
              <option v-for="header in headers" :key="header" :value="header">{{ header }}</option>
            </select>
          </div>
          <div class="nav-buttons">
            <button @click="prevStep">⬅️ {{ t('common.previous') }}</button>
            <button @click="goToStep(3)">{{ t('common.next') }} ➡️</button>
          </div>
        </div>

        <!-- Step 3: Selecció de categoria i subcategoria -->
        <div v-else-if="currentStep === 3">
          <label>{{ t('import.optionalCategory') }}</label>
          <select v-model="form.categoria">
            <option disabled :value="null">{{ t('import.chooseCategory') }}</option>
            <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
              {{ cat.nom }}
            </option>
          </select>

          <label>{{ t('import.optionalSubcategory') }}</label>
          <select v-model="form.subcategoria" :disabled="!form.categoria">
            <option disabled :value="null">{{ t('import.chooseSubcategory') }}</option>
            <option v-for="sub in filteredSubcategories" :key="sub.id" :value="sub.id">
              {{ sub.nom }}
            </option>
          </select>

          <div class="nav-buttons">
            <button @click="prevStep">⬅️ {{ t('common.previous') }}</button>
            <button @click="mapAndPreview">{{ t('common.next') }} ➡️</button>
          </div>
        </div>

        <!-- Step 4: Previsualització i edició -->
        <div v-else-if="currentStep === 4">
          <h4>{{ t('import.preview') }}</h4>
          <table>
            <thead>
              <tr>
                <!-- Mostra els camps extrets (sense categoria i subcategoria) -->
                <th v-for="col in modelFields" :key="col">{{ t('fields.' + col) }}</th>
                <!-- Columnes separades per categoria i subcategoria -->
                <th>{{ t('fields.categoria') }}</th>
                <th>{{ t('fields.subcategoria') }}</th>
                <th>{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(product, index) in preview" :key="index">
                <td v-for="field in modelFields" :key="field">
                  <input
                    :class="{ error: hasError(index, field) }"
                    v-model="product[field]"
                    @blur="sanitizeField(index, field)"
                  />
                </td>
                <td>
                  <select v-model="product.categoria">
                    <option disabled :value="null">{{ t('import.chooseCategory') }}</option>
                    <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                      {{ cat.nom }}
                    </option>
                  </select>
                </td>
                <td>
                  <select v-model="product.subcategoria" :disabled="!product.categoria">
                    <option disabled :value="null">{{ t('import.chooseSubcategory') }}</option>
                    <option v-for="sub in categories.filter(c => c.parent_id == product.categoria)" :key="sub.id" :value="sub.id">
                      {{ sub.nom }}
                    </option>
                  </select>
                </td>
                <td><button @click="removeRow(index)">🗑️</button></td>
              </tr>
            </tbody>
          </table>
          <p>* {{ t('import.editNote') }}</p>
          <div class="nav-buttons">
            <button @click="prevStep">⬅️ {{ t('common.previous') }}</button>
            <button @click="handleUpload">{{ t('import.upload') }} 🚀</button>
          </div>
        </div>

        <!-- Step 5: Resultat de la importació -->
        <div v-else-if="currentStep === 5">
          <p>{{ result.message }}</p>
          <p>{{ t('import.success', { count: result.importats }) }}</p>
          <div v-if="result.errors.length">
            <h4>{{ t('import.errorsFound') }}</h4>
            <ul>
              <li v-for="error in result.errors" :key="error.fila">
                {{ t('import.row') }} {{ error.fila }}: {{ formatError(error.errors) }}
              </li>
            </ul>
            <button @click="downloadErrorExcel">{{ t('import.downloadErrors') }} 📥</button>
          </div>
          <button @click="reset">{{ t('common.close') }} ❌</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { saveAs } from 'file-saver';
import * as XLSX from 'xlsx';

const { t } = useI18n();
const emit = defineEmits(['close', 'refresh']);
const props = defineProps<{ botigues: { id: number; nom: string }[] }>();

const API_URL = 'http://127.0.0.1:8000/api';

const currentStep = ref(1);
const form = ref({
  botiga_id: '',
  categoria: null as number | null,
  subcategoria: null as number | null,
});
const file = ref<File | null>(null);
const headers = ref<string[]>([]);
const mapping = ref<Record<string, string>>({});
const preview = ref<any[]>([]);
const result = ref({ importats: 0, errors: [] as any[], message: '' });
const categories = ref<any[]>([]);

const modelFields = ['nom', 'preu', 'stock', 'descripcio', 'imatge'];
const steps = [
  { label: 'import.step1' },
  { label: 'import.step2' },
  { label: 'import.step3' },
  { label: 'import.step4' },
  { label: 'import.step5' },
];

// Carrega les categories des del backend
onMounted(async () => {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/categories`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    categories.value = response.data;
  } catch {
    console.warn("No s'han pogut carregar les categories");
  }
});

// Computed per obtenir les categories pare
const parentCategories = computed(() => {
  return categories.value.filter(cat => cat.parent_id == null);
});

// Computed per filtrar subcategories segons la categoria seleccionada en el formulari
const filteredSubcategories = computed(() => {
  return categories.value.filter(cat => cat.parent_id == form.value.categoria);
});

// Navegació entre passos
function goToStep(n: number) { currentStep.value = n; }
function nextStep() { currentStep.value++; }
function prevStep() { currentStep.value--; }
function reset() {
  currentStep.value = 1;
  file.value = null;
  headers.value = [];
  mapping.value = {};
  preview.value = [];
  result.value = { importats: 0, errors: [], message: '' };
  emit('close');
}

// Gestió del fitxer i extracció de capçaleres
function handleFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    file.value = target.files[0];
    extractHeaders();
  }
}

function extractHeaders() {
  if (!file.value) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target?.result as ArrayBuffer);
    const workbook = XLSX.read(data, { type: 'array' });
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const json = XLSX.utils.sheet_to_json(sheet, { header: 1 });
    headers.value = json[0] as string[];
    autoMapFields();
    currentStep.value = 2;
  };
  reader.readAsArrayBuffer(file.value);
}

function autoMapFields() {
  const fieldKeywords: Record<string, string[]> = {
    nom: ['nom', 'name', 'product'],
    preu: ['preu', 'price', 'cost'],
    stock: ['stock', 'unitats', 'quantitat', 'total'],
    descripcio: ['descripcio', 'description', 'info'],
    categoria: ['categoria', 'category'],
    imatge: ['imatge', 'image', 'img'],
  };

  modelFields.forEach(field => {
    const keywords = fieldKeywords[field];
    const match = headers.value.find(h => 
      keywords.some(k => h.toLowerCase().includes(k))
    );
    if (match) mapping.value[field] = match;
  });
}

function availableHeaders(currentField: string) {
  const selected = Object.entries(mapping).filter(([key]) => key !== currentField).map(([, val]) => val);
  return headers.value.filter(h => !selected.includes(h));
}

// Genera la previsualització basant-se en el fitxer i el mapatge; assigna els valors comuns de categoria i subcategoria
function mapAndPreview() {
  if (!file.value) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target?.result as ArrayBuffer);
    const workbook = XLSX.read(data, { type: 'array' });
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const raw = XLSX.utils.sheet_to_json(sheet, { header: 1 });
    const dataRows = raw.slice(1);

    preview.value = dataRows.map(row => {
      const obj: any = {};
      modelFields.forEach(field => {
        const header = mapping.value[field];
        if (header) {
          const index = headers.value.indexOf(header);
          if (index !== -1) obj[field] = row[index];
        }
      });
      // Converteix preu i stock
      if (obj.preu) obj.preu = parseFloat(String(obj.preu).replace(',', '.')) || 0;
      if (obj.stock) {
        const clean = String(obj.stock).replace(/[^\d.]/g, '').replace(',', '.');
        obj.stock = Number.isFinite(parseFloat(clean)) ? Math.round(parseFloat(clean)) : 0;
      }
      // Assigna els IDs de categoria i subcategoria del formulari
      obj.categoria = form.value.categoria;
      obj.subcategoria = form.value.subcategoria;
      return obj;
    });

    currentStep.value = 4;
  };
  reader.readAsArrayBuffer(file.value);
}

function sanitizeField(index: number, field: string) {
  const value = preview.value[index][field];
  if (field === 'preu') preview.value[index][field] = parseFloat(String(value).replace(',', '.')) || 0;
  if (field === 'stock') preview.value[index][field] = parseInt(String(value).replace(/[^\d]/g, '')) || 0;
}

function hasError(index: number, field: string) {
  const row = result.value.errors.find(e => e.fila === index + 2);
  return row?.errors?.[field];
}

function formatError(errors: Record<string, string>) {
  return Object.entries(errors).map(([k, v]) => `${k}: ${v}`).join(', ');
}

// Pujar la previsualització i enviar la request d'importació
function handleUpload() {
  const formData = new FormData();
  if (file.value) formData.append('fitxer', file.value);
  formData.append('botiga_id', form.value.botiga_id);
  formData.append('categoria', form.value.categoria ? form.value.categoria.toString() : '');
  formData.append('subcategoria', form.value.subcategoria ? form.value.subcategoria.toString() : '');
  formData.append('preview', JSON.stringify(preview.value));

  const token = localStorage.getItem('userToken');
  axios.post(`${API_URL}/import-productes`, formData, {
    headers: { Authorization: `Bearer ${token}` },
  })
    .then(res => {
      result.value = res.data;
      currentStep.value = 5;
      emit('refresh');
    })
    .catch(() => {
      result.value.message = t('import.errorUpload');
      currentStep.value = 5;
    });
}

function downloadErrorExcel() {
  const errorSheet = preview.value.map((row, i) => {
    const base = { ...row };
    const error = result.value.errors.find(e => e.fila === i + 2);
    if (error) base['__ERRORS__'] = JSON.stringify(error.errors);
    return base;
  });
  const worksheet = XLSX.utils.json_to_sheet(errorSheet);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, worksheet, 'Errors');
  const blob = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
  saveAs(new Blob([blob]), 'errors.xlsx');
}

function removeRow(index: number) {
  preview.value.splice(index, 1);
}

onMounted(() => {
  // Es poden carregar altres dades si és necessari, per exemple botigues i categories
});
</script>

<style scoped>
.wizard {
  position: fixed;
  top: 60px;
  left: 0;
  width: 100%;
  z-index: 1000;
}
.modal {
  background: rgba(0, 0, 0, 0.5);
  padding: 20px;
}
.modal-content {
  background: white;
  padding: 30px;
  max-width: 700px;
  margin: auto;
  border-radius: 10px;
  position: relative;
}
.close-btn {
  position: absolute;
  top: 15px;
  right: 15px;
  background: transparent;
  border: none;
  font-size: 20px;
  cursor: pointer;
}
.steps {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
  font-size: 14px;
  cursor: pointer;
}
.steps span {
  padding: 5px 10px;
  border-bottom: 2px solid #ccc;
}
.steps span.active {
  border-color: #42b983;
  font-weight: bold;
}
.steps span.clickable:hover {
  text-decoration: underline;
  color: #42b983;
}
input,
select {
  display: block;
  margin: 10px 0;
  padding: 8px;
  width: 100%;
}
input.error {
  border: 2px solid red;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}
th,
td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: center;
}
button {
  background: #42b983;
  color: white;
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  margin-top: 10px;
}
button:hover {
  background: #368c6e;
}
.nav-buttons {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
}
</style>

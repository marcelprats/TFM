<template>
  <div class="wizard">
    <div class="modal">
      <div class="modal-content">
        <button class="close-button" @click="reset">✖</button>
        <h2>{{ t('import.title') }}</h2>

        <div class="steps">
          <span
            v-for="(step, i) in steps"
            :key="i"
            :class="{ active: currentStep === i + 1, clickable: currentStep > i + 1 }"
            @click="currentStep > i + 1 ? currentStep = i + 1 : null"
          >
            {{ i + 1 }}. {{ t(step.label) }}
          </span>
        </div>

        <!-- Step 1 -->
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

        <!-- Step 2: Mapping -->
        <div v-else-if="currentStep === 2">
        <h4>{{ t('import.mapColumns') }}</h4>
        <div v-for="field in modelFields" :key="field">
          <label>{{ t('fields.' + field) }}</label>
          <select v-model="mapping[field]">
            <option value="">{{ t('import.ignore') }}</option>
            <option v-for="header in headers" :key="header" :value="header">{{ header }}</option>
          </select>
        </div>
        <button @click="mapAndPreview">{{ t('common.next') }}</button>
        <button @click="prevStep">{{ t('common.previous') }}</button>
        </div>


        <!-- Step 3 -->
        <div v-else-if="currentStep === 3">
          <label>{{ t('import.optionalCategory') }}</label>
          <input v-model="form.categoria" placeholder="Fruita, Carn..." />
          <div class="nav-buttons">
            <button @click="currentStep--">{{ t('common.previous') }}</button>
            <button @click="currentStep++">{{ t('common.next') }}</button>
          </div>
        </div>

        <!-- Step 4 -->
        <div v-else-if="currentStep === 4">
          <h4>{{ t('import.preview') }}</h4>
          <table>
            <thead>
              <tr>
                <th v-for="col in modelFields" :key="col">{{ t('fields.' + col) }}</th>
                <th>{{ t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(product, index) in preview" :key="index">
                <td v-for="field in modelFields" :key="field">
                  <input
                    :class="{ error: hasError(index, field) }"
                    v-model="preview[index][field]"
                    @blur="sanitizeField(index, field)"
                  />
                </td>
                <td><button @click="removeRow(index)">🗑️</button></td>
              </tr>
            </tbody>
          </table>
          <p>* {{ t('import.editNote') }}</p>
          <div class="nav-buttons">
            <button @click="currentStep--">{{ t('common.previous') }}</button>
            <button @click="handleUpload">{{ t('import.upload') }}</button>
          </div>
        </div>

        <!-- Step 5 -->
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
            <button @click="downloadErrorExcel">{{ t('import.downloadErrors') }}</button>
          </div>

          <button @click="reset">{{ t('common.close') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { saveAs } from 'file-saver';
import * as XLSX from 'xlsx';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const API_URL = 'http://127.0.0.1:8000/api';

const props = defineProps<{ botigues: { id: number; nom: string }[] }>();
const emit = defineEmits(['close', 'refresh']);

const currentStep = ref(1);
const file = ref<File | null>(null);
const headers = ref<string[]>([]);
const mapping = ref<Record<string, string>>({});
const preview = ref<any[]>([]);
const result = ref({ importats: 0, errors: [], message: '' });

const form = ref({ botiga_id: '', categoria: '' });
const modelFields = ['nom', 'preu', 'stock', 'descripcio', 'categoria', 'imatge'];
const steps = [
  { label: 'import.step1' },
  { label: 'import.step2' },
  { label: 'import.step3' },
  { label: 'import.step4' },
  { label: 'import.step5' },
];

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    file.value = target.files[0];
    extractHeaders();
  }
};

const extractHeaders = async () => {
  if (!file.value) return;
  const reader = new FileReader();
  reader.onload = e => {
    const data = new Uint8Array(e.target?.result as ArrayBuffer);
    const workbook = XLSX.read(data, { type: 'array' });
    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
    const json = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
    headers.value = json[0] as string[];
    currentStep.value = 2;
  };
  reader.readAsArrayBuffer(file.value);
};

const mapAndPreview = async () => {
  const reader = new FileReader();
  reader.onload = e => {
    const data = new Uint8Array(e.target?.result as ArrayBuffer);
    const workbook = XLSX.read(data, { type: 'array' });
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const raw = XLSX.utils.sheet_to_json(sheet, { header: 1 });
    const dataRows = raw.slice(1);

    preview.value = dataRows.map(row => {
      const obj: any = {};
      modelFields.forEach(field => {
        const sourceColumn = mapping.value[field];
        if (sourceColumn) {
          const index = headers.value.indexOf(sourceColumn);
          if (index !== -1) {
            obj[field] = row[index];
          }
        }
      });

      // Neteja forta
      if (obj.preu !== undefined && obj.preu !== null) {
        obj.preu = parseFloat(String(obj.preu).toString().replace(',', '.')) || 0;
      }
      if (obj.stock !== undefined && obj.stock !== null) {
        const cleaned = String(obj.stock).replace(/[^\d.]/g, '').replace(',', '.');
        const number = parseFloat(cleaned);
        obj.stock = Number.isFinite(number) ? Math.round(number) : 0;
      }

      return obj;
    });

    console.log('🔍 Preview netejada i generada:', preview.value);
    currentStep.value = 3;
  };
  reader.readAsArrayBuffer(file.value);
};

const handleUpload = async () => {
  const formData = new FormData();
  if (file.value) formData.append('fitxer', file.value);
  formData.append('botiga_id', form.value.botiga_id);
  formData.append('categoria', form.value.categoria || '');
  formData.append('preview', JSON.stringify(preview.value));

  const token = localStorage.getItem('userToken');
  try {
    const response = await axios.post(`${API_URL}/import-productes`, formData, {
      headers: { Authorization: `Bearer ${token}` },
    });
    console.log('✅ Resposta del backend:', response.data);
    result.value = response.data;
    currentStep.value = 5;
    emit('refresh');
  } catch (error) {
    console.error('❌ Error a la importació:', error);
    result.value.message = t('import.errorUpload');
    currentStep.value = 5;
  }
};

const hasError = (index: number, field: string) => {
  const row = result.value.errors.find(e => e.fila === index + 2);
  return row?.errors?.[field];
};

const removeRow = (index: number) => {
  preview.value.splice(index, 1);
};

const downloadErrorExcel = () => {
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
};

const nextStep = () => currentStep.value++;
const prevStep = () => currentStep.value--;
const reset = () => {
  currentStep.value = 1;
  file.value = null;
  mapping.value = {};
  preview.value = [];
  result.value = { importats: 0, errors: [], message: '' };
  emit('close');
};

const formatError = (errors: Record<string, string>) => {
  return Object.entries(errors).map(([k, v]) => `${k}: ${v}`).join(', ');
};
</script>


<style scoped>
.wizard { position: fixed; top: 60px; left: 0; width: 100%; z-index: 1000; }
.modal { background: rgba(0, 0, 0, 0.5); padding: 20px; }
.modal-content { background: white; padding: 30px; max-width: 700px; margin: auto; border-radius: 10px; position: relative; }
.close-button { position: absolute; top: 15px; right: 15px; background: transparent; border: none; font-size: 20px; cursor: pointer; }
.steps { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; cursor: pointer; }
.steps span { padding: 5px 10px; border-bottom: 2px solid #ccc; }
.steps span.active { border-color: #42b983; font-weight: bold; }
.steps span.clickable:hover { text-decoration: underline; color: #42b983; }
input, select { display: block; margin: 10px 0; padding: 8px; width: 100%; }
input.error { border: 2px solid red; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
button { background: #42b983; color: white; padding: 8px 12px; border: none; border-radius: 4px; margin-top: 10px; }
button:hover { background: #368c6e; }
.nav-buttons { display: flex; justify-content: space-between; margin-top: 20px; }
</style>

import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import { createI18n } from 'vue-i18n';
import { createPinia } from 'pinia';
import ca from './locales/ca.json';
import es from './locales/es.json';
import axios from "axios";
import "leaflet/dist/leaflet.css";
import "./style.css";

axios.defaults.baseURL = "http://127.0.0.1:8000/api"; // Base URL de l'API Laravel
const token = localStorage.getItem("userToken");  // Comproveu que aquest valor existeix
if (token) {
  axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
}
const i18n = createI18n({
  legacy: false,
  locale: 'ca',
  fallbackLocale: 'es',
  messages: { ca, es }
});

const app = createApp(App);
const pinia = createPinia();
app.use(pinia);
app.use(router);
app.use(i18n);
app.mount("#app");

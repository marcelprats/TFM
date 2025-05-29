// main.ts
import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import { createI18n } from "vue-i18n";
import { createPinia } from "pinia";
import ca from "./locales/ca.json";
import es from "./locales/es.json";
import axios from "axios";
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import "vue-multiselect/dist/vue-multiselect.css";
import "leaflet/dist/leaflet.css";
import "./style.css";

// 🔧 BASE URL dinàmica segons l’entorn
const BACKEND = import.meta.env.VITE_BACKEND_URL;
const API_BASE = import.meta.env.VITE_API_BASE || "/api";


// Axios global config
axios.defaults.baseURL = `${BACKEND}${API_BASE}`;
axios.defaults.withCredentials = true;           // ← Important perquè funcioni Sanctum
// Només enviem l’Authorization si tenim token
const token = localStorage.getItem("userToken");
if (token) {
  axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
}

const i18n = createI18n({
  legacy: false,
  locale: "ca",
  fallbackLocale: "es",
  messages: { ca, es },
});

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.use(Toast);
app.use(i18n);
app.mount("#app");

import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import axios from "axios";
import "./style.css";

axios.defaults.baseURL = "http://127.0.0.1:8000/api"; // Base URL de l'API Laravel
axios.defaults.headers.common["Authorization"] =
  "Bearer " + localStorage.getItem("token"); // Afegir token si existeix

const app = createApp(App);
app.use(router);
app.mount("#app");

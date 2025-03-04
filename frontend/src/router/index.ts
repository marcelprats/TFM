import { createRouter, createWebHistory } from "vue-router";
import Home from "../views/Home.vue";
import Login from "../views/Login.vue";
import Register from "../views/Register.vue";
import RegisterVendor from "../views/RegisterVendor.vue";
import UserProfile from "../views/UserProfile.vue";
import AreaPersonalBotigues from "../views/AreaPersonalBotigues.vue";
import AreaPersonalProductes from "../views/AreaPersonalProductes.vue";
import Botiga from "../views/Botiga.vue";
import Producte from "../views/Producte.vue";
import InfoBotiga from "../views/InfoBotiga.vue";
import InfoVenedor from "../views/InfoVenedor.vue";
import { isLoggedIn, getUserType } from "../services/authService";

const routes = [
  { path: "/", name: "Home", component: Home },
  { path: "/login", name: "Login", component: Login },
  { path: "/register", name: "Register", component: Register },
  { path: "/register-vendor", name: "RegisterVendor", component: RegisterVendor },
  { path: "/perfil", name: "UserProfile", component: UserProfile, meta: { requiresAuth: true } },
  { path: "/area-personal-botigues", name: "AreaPersonalBotigues", component: AreaPersonalBotigues, meta: { requiresAuth: true, requiresVendor: true } },
  { path: "/area-personal-productes", name: "AreaPersonalProductes", component: AreaPersonalProductes, meta: { requiresAuth: true, requiresVendor: true } },
  { path: "/botiga", component: Botiga },
  { path: "/producte/:id", component: Producte, name: "Producte" },
  { path: "/info-botiga/:id", component: InfoBotiga, name: "InfoBotiga" },
  { path: "/info-venedor/:id", component: InfoVenedor, name: "InfoVenedor" }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Middleware per restringir l'accés a venedors
router.beforeEach((to, from, next) => {
  if (to.meta.requiresAuth && !isLoggedIn()) {
    next("/login");
  } else if (to.meta.requiresVendor && getUserType() !== "vendor") {
    next("/");
  } else {
    next();
  }
});

export default router;

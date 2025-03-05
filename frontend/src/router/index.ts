import { createRouter, createWebHistory } from "vue-router";
import Home from "../views/Home.vue";
import Login from "../views/Login.vue";
import Register from "../views/Register.vue";
import RegisterVendor from "../views/RegisterVendor.vue";
import UserProfile from "../views/UserProfile.vue";
import AreaPersonal from "../views/AreaPersonal.vue";
import AreaPersonalBotigues from "../views/AreaPersonalBotigues.vue";
import AreaPersonalProductes from "../views/AreaPersonalProductes.vue";
import Botiga from "../views/Botiga.vue";
import Producte from "../views/Producte.vue";
import InfoBotiga from "../views/InfoBotiga.vue";
import InfoVenedor from "../views/InfoVenedor.vue";
import InfoVenedors from "../views/InfoVenedors.vue";
import InfoBotigues from "../views/InfoBotigues.vue";
import { isLoggedIn, getUserType } from "../services/authService";

const routes = [
  { path: "/", name: "Home", component: Home },
  { path: "/login", name: "Login", component: Login },
  { path: "/register", name: "Register", component: Register },
  { path: "/register-vendor", name: "RegisterVendor", component: RegisterVendor },
  { path: "/perfil", name: "UserProfile", component: UserProfile, meta: { requiresAuth: true } },
  { path: "/area-personal", component: AreaPersonal },
  { path: "/area-personal-botigues", name: "AreaPersonalBotigues", component: AreaPersonalBotigues, meta: { requiresAuth: true, requiresVendor: true } },
  { path: "/area-personal-productes", name: "AreaPersonalProductes", component: AreaPersonalProductes, meta: { requiresAuth: true, requiresVendor: true } },
  { path: "/botiga", component: Botiga },
  { path: "/producte/:id", component: Producte, name: "Producte" },
  { path: "/info-venedor", name: "InfoVenedors", component: InfoVenedors },
  { path: "/info-venedor/:id", name: "InfoVenedor", component: InfoVenedor },
  { path: "/info-botiga", name: "InfoBotigues", component: InfoBotigues },
  { path: "/info-botiga/:id", name: "InfoBotiga", component: InfoBotiga },
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

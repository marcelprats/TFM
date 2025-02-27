import { createRouter, createWebHistory } from 'vue-router';
import Home from '../views/Home.vue';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import RegisterVendor from '../views/RegisterVendor.vue';  // 🆕 Afegim la vista
import UserProfile from '../views/UserProfile.vue';
import { isLoggedIn } from '../services/authService';

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/login', name: 'Login', component: Login },
  { path: '/register', name: 'Register', component: Register },
  { path: '/register-vendor', name: 'RegisterVendor', component: RegisterVendor }, // 🆕 Ruta per a venedors
  { path: '/perfil', name: 'UserProfile', component: UserProfile, meta: { requiresAuth: true } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {  // 🆕 Corregit el `from`
  if (to.meta.requiresAuth && !isLoggedIn()) {
    next('/login');
  } else {
    next();
  }
});

export default router;

import axios from "axios";

const API_URL = "http://127.0.0.1:8000/api";

// Registra un nou usuari (comprador)
export const registerUser = async (name: string, email: string, password: string) => {
  const response = await axios.post(`${API_URL}/register`, {
    name,
    email,
    password,
  });
  return response.data;
};

// Registra un nou venedor
export const registerVendor = async (name: string, email: string, password: string) => {
  const response = await axios.post(`${API_URL}/register-vendor`, {
    name,
    email,
    password,
  });
  return response.data;
};

// Funció comuna per fer login, diferenciant si és comprador o venedor
export const loginUser = async (email: string, password: string, isVendor: boolean) => {
  const endpoint = isVendor ? "/login-vendor" : "/login"; // Decideix si és vendor o user
  const response = await axios.post(`${API_URL}${endpoint}`, { email, password });

  if (response.data.token) {
    localStorage.setItem("userToken", response.data.token);
    localStorage.setItem("user", JSON.stringify(response.data.user));
    localStorage.setItem("userType", isVendor ? "vendor" : "user"); // Guardem el tipus d'usuari
  }

  return response.data.user;
};

// Comprova si l'usuari està autenticat
export const isLoggedIn = () => {
  return !!localStorage.getItem("userToken");
};

// Obté les dades de l'usuari autenticat
export const fetchUser = async () => {
  const token = localStorage.getItem("userToken");
  if (!token) return null;

  try {
    const response = await axios.get(`${API_URL}/user`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    localStorage.setItem("user", JSON.stringify(response.data));
    return response.data;
  } catch {
    return null;
  }
};

// Retorna l'usuari si està guardat en `localStorage`
export const getUser = () => {
  const user = localStorage.getItem("user");
  return user ? JSON.parse(user) : null;
};

// Retorna quin tipus d'usuari és
export const getUserType = () => {
  return localStorage.getItem("userType") || "user"; // Per defecte, "user"
};

// Tanca sessió i elimina el token
export const logout = () => {
  localStorage.removeItem("userToken");
  localStorage.removeItem("user");
  localStorage.removeItem("userType"); // Eliminem també el tipus d'usuari
};

// Actualitza les dades de l'usuari
export async function updateUser(userData: { name: string; email: string }) {
  try {
    const token = localStorage.getItem("userToken");
    const response = await axios.put(`${API_URL}/user`, userData, {
      headers: { Authorization: `Bearer ${token}` },
    });

    localStorage.setItem("user", JSON.stringify(response.data)); // Actualitzem dades a `localStorage`
    return { success: true, user: response.data };
  } catch (error) {
    console.error("Error actualitzant l'usuari:", error);
    return { success: false };
  }
}

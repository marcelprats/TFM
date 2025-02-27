import axios from "axios";

const API_URL = "http://127.0.0.1:8000/api";

// Registra un nou usuari
export const registerUser = async (name: string, email: string, password: string) => {
  const response = await axios.post(`${API_URL}/register`, {
    name,
    email,
    password,
  });
  return response.data;
};

export async function registerVendor(userData: { name: string; email: string; password: string }) {
    return await axios.post(`${API_URL}/register-vendor`, userData);
}


// Inicia sessió i guarda el token
export const loginUser = async (email: string, password: string) => {
  const response = await axios.post(`${API_URL}/login`, {
    email,
    password,
  });

  if (response.data.token) {
    localStorage.setItem("userToken", response.data.token);
  }
  return response.data;
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
    return response.data;
  } catch {
    return null;
  }
};

// Retorna l'usuari si està guardat en localStorage
export const getUser = () => {
  const user = localStorage.getItem("user");
  return user ? JSON.parse(user) : null;
};

// Tanca sessió i elimina el token
export const logout = () => {
  localStorage.removeItem("userToken");
  localStorage.removeItem("user");
};


export async function updateUser(userData: { name: string; email: string }) {
    try {
      const token = localStorage.getItem("token");
      const response = await axios.put("http://127.0.0.1:8000/api/user", userData, {
        headers: { Authorization: `Bearer ${token}` },
      });
      return { success: true, user: response.data };
    } catch (error) {
      console.error("Error actualitzant l'usuari:", error);
      return { success: false };
    }
  }
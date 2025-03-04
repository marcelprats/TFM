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
    console.log("Dades enviades a /register-vendor:", { name, email, password }); // 👈 Afegit per depuració
  
    try {
      const response = await axios.post(`${API_URL}/register-vendor`, {
        name: String(name),  // 👈 Assegura't que són strings
        email: String(email),
        password: String(password),
      });
      return response.data;
    } catch (error: any) {
      console.error("Error en el registre:", error.response?.data || error.message);
      return { success: false, message: error.response?.data?.message || "Error desconegut" };
    }
  };
  
  

// Funció comuna per fer login, diferenciant si és comprador o venedor
export const loginUser = async (email: string, password: string, isVendor: boolean) => {
  const response = await axios.post(`${API_URL}/login`, { email, password, is_vendor: isVendor });

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
      headers: { Authorization: `Bearer ${token}` },
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

export const fetchProducts = async () => {
  try {
    const response = await axios.get(`${API_URL}/productes-tots`);

    if (!Array.isArray(response.data)) {
      console.error("Error: L'API no retorna una llista de productes", response.data);
      return [];
    }

    console.log("Productes rebuts:", response.data); // Depuració

    return response.data.map(product => ({
      id: product.id,
      name: product.nom,
      description: product.descripcio,
      price: product.preu || 0,
      stores: product.botigues ? product.botigues.map((b: any) => ({ id: b.id, name: b.nom })) : [],
      vendor: product.vendor ? { id: product.vendor.id, name: product.vendor.name } : null // ✅ Assignació correcta del venedor
    }));

  } catch (error) {
    console.error("Error obtenint productes:", error);
    return [];
  }
};



export const fetchProductById = async (id: string) => {
  try {
    const response = await axios.get(`${API_URL}/productes/${id}`);
    return response.data;
  } catch (error) {
    console.error("Error obtenint el producte:", error);
    return null;
  }
};

export const fetchVendorById = async (id: string) => {
  try {
    const response = await axios.get(`${API_URL}/vendors/${id}`);
    return response.data;
  } catch (error) {
    console.error("Error obtenint el venedor:", error);
    return null;
  }
};

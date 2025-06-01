import axios from "axios";

// L’arrel del Laravel
const ROOT = import.meta.env.VITE_BACKEND_URL || "";

// ─── Registra un nou usuari (comprador) ─────────────────────────
export const registerUser = async (
  name: string,
  email: string,
  password: string
) => {
  const response = await axios.post("/register", { name, email, password });
  return response.data;
};

// ─── Registra un nou venedor ────────────────────────────────────
export const registerVendor = async (
  name: string,
  email: string,
  password: string
) => {
  console.log("Dades enviades a /register-vendor:", {
    name,
    email,
    password,
  });
  try {
    const response = await axios.post("/register-vendor", {
      name,
      email,
      password,
    });
    return response.data;
  } catch (error: any) {
    console.error("Error en el registre:", error.response?.data || error.message);
    return {
      success: false,
      message: error.response?.data?.message || "Error desconegut",
    };
  }
};

// ─── Login (comprador o venedor) ─────────────────────────────────
export const loginUser = async (
  email: string,
  password: string,
  isVendor: boolean
) => {
  try {
    // 1) Primer, demana el CSRF cookie perquè Sanctum pugui validar el POST
    await axios.get(`${ROOT}/sanctum/csrf-cookie`);

    // 2) Després, fa el login
    const response = await axios.post("/login", {
      email,
      password,
      is_vendor: isVendor,
    });

    const { token, user, role } = response.data;
    if (token) {
      localStorage.setItem("userToken", token);
      localStorage.setItem("user", JSON.stringify(user));
      localStorage.setItem("userType", role || (isVendor ? "vendor" : "user"));

      axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
      window.dispatchEvent(new Event("authChange"));
    }

    return user;
  } catch (error: any) {
    console.error("Error en loginUser:", error.response?.data || error.message);
    throw error;
  }
};

// ─── Estats i dades d’usuari ────────────────────────────────────
export const isLoggedIn = (): boolean => !!localStorage.getItem("userToken");

export const fetchUser = async () => {
  const token = localStorage.getItem("userToken");
  if (!token) return null;

  try {
    const response = await axios.get("/user");
    localStorage.setItem("user", JSON.stringify(response.data));
    return response.data;
  } catch {
    return null;
  }
};

export const getUser = () => {
  const user = localStorage.getItem("user");
  return user ? JSON.parse(user) : null;
};

export const getUserType = (): string =>
  localStorage.getItem("userType") || "user";

export const logout = () => {
  localStorage.removeItem("userToken");
  localStorage.removeItem("user");
  localStorage.removeItem("userType");
  delete axios.defaults.headers.common["Authorization"];
  window.dispatchEvent(new Event("authChange"));
};

// ─── Actualitza dades d’usuari ──────────────────────────────────
export async function updateUser(userData: {
  name: string;
  email: string;
}) {
  try {
    const response = await axios.put("/user", userData);
    localStorage.setItem("user", JSON.stringify(response.data));
    return { success: true, user: response.data };
  } catch (error) {
    console.error("Error actualitzant l'usuari:", error);
    return { success: false };
  }
}

// ─── Productes ──────────────────────────────────────────────────
export const fetchProducts = async () => {
  try {
    const response = await axios.get("/productes-tots");
    if (!Array.isArray(response.data)) {
      console.error("L'API no retorna una llista de productes", response.data);
      return [];
    }
    return response.data.map((product: any) => ({
      id: product.id,
      name: product.nom,
      description: product.descripcio,
      price: parseFloat(product.preu) || 0,
      store: product.botiga
        ? { id: product.botiga.id, name: product.botiga.nom }
        : null,
      vendor: product.vendor
        ? { id: product.vendor.id, name: product.vendor.name }
        : null,
    }));
  } catch (error) {
    console.error("Error obtenint productes:", error);
    return [];
  }
};

export const fetchProductById = async (id: string) => {
  try {
    const response = await axios.get(`/productes/${id}`);
    return response.data;
  } catch (error) {
    console.error("Error obtenint el producte:", error);
    return null;
  }
};

export const fetchVendorById = async (id: string) => {
  try {
    const response = await axios.get(`/vendors/${id}`);
    return response.data;
  } catch (error) {
    console.error("Error obtenint el venedor:", error);
    return null;
  }
};

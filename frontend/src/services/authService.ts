import axios from "axios";

const API_URL = "http://127.0.0.1:8000/api";

// Registra un usuari
export async function registerUser(name: string, email: string, password: string) {
    try {
        const response = await axios.post(`${API_URL}/register`, {
            name,
            email,
            password
        });
        return response.data;
    } catch (error: any) {
        console.error("Error en el registre:", error.response?.data || error.message);
        throw error.response?.data || error.message;
    }
}

// Comprova si hi ha un token guardat
export function isLoggedIn(): boolean {
    return !!localStorage.getItem("token");
}

// Retorna la informació de l'usuari des de localStorage
export function getUser() {
    return JSON.parse(localStorage.getItem("user") || "null");
}

// Logout: Elimina el token i l'usuari
export function logout() {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    window.location.reload();
}

// Funció per obtenir les dades de l'usuari des de l'API
export async function fetchUser() {
    try {
        const response = await axios.get(`${API_URL}/user`, {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
        });
        localStorage.setItem("user", JSON.stringify(response.data));
        return response.data;
    } catch (error) {
        console.error("Error obtenint l'usuari:", error);
        logout();
    }
}

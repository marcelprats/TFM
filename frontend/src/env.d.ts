/// <reference types="vite/client" />

// Defineix les variables d’entorn exposades per Vite
interface ImportMetaEnv {
  // URL del backend (sense `/api` al final)
  readonly VITE_BACKEND_URL: string;
  // (Opcional) URL de l’API, si la vols extraure per separat
  readonly VITE_API_URL?: string;
  // Afegeix aquí qualsevol altra variable VITE_* que utilitzis
  // readonly VITE_ALTRE_VARIABLE: string;
}

// Integra ImportMetaEnv a import.meta.env
interface ImportMeta {
  readonly env: ImportMetaEnv;
}

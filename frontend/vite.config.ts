// vite.config.ts
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig(({ mode }) => {
  // 1. Carrega les variables d'entorn de .env, .env.development o .env.production
  const env = loadEnv(mode, process.cwd(), '')
  const BACKEND_URL = env.VITE_BACKEND_URL

  if (!BACKEND_URL) {
    throw new Error('Has d\'especificar VITE_BACKEND_URL en el teu .env')
  }

  return {
    plugins: [
      vue(),
    ],

    // 2. Proxy per dev
    server: mode === 'development' ? {
      proxy: {
        '/api': {
          target: BACKEND_URL,
          changeOrigin: true,
          secure: false,
          rewrite: (path) => path.replace(/^\/api/, '/api'),
        },
      },
    } : undefined,

    // 3. Exposa la URL al client
    define: {
      'import.meta.env.VITE_BACKEND_URL': JSON.stringify(BACKEND_URL),
    },

    // 4. Opcions de build
    build: {
      // Si vols servir el teu app des d'un subdirectori, descomenta i posa aquí
      // base: '/el-teu-subdirectori/',
      target: 'esnext',
      sourcemap: mode === 'development',
      chunkSizeWarningLimit: 1500,
    },

    // 5. Resolució de mòduls si necessites alias
    resolve: {
      alias: {
        '@': `${process.cwd()}/src`,
      },
    },
  }
})

import { createI18n } from 'vue-i18n'

const messages = {
  ca: {
    importTitle: 'Importar Productes',
    step1: '1. Selecció',
    step2: '2. Vinculació',
    step3: '3. Categoria',
    step4: '4. Previsualització',
    step5: '5. Resultat',
    upload: 'Puja un fitxer Excel',
    importar: 'Importar',
    tancar: 'Tancar',
    error: 'S\'ha produït un error',
    previewTitle: 'Previsualització dels productes:',
    modifyNote: '* Pots modificar o eliminar valors abans d\'importar',
  },
  es: {
    importTitle: 'Importar Productos',
    step1: '1. Selección',
    step2: '2. Vinculación',
    step3: '3. Categoría',
    step4: '4. Previsualización',
    step5: '5. Resultado',
    upload: 'Sube un archivo Excel',
    importar: 'Importar',
    tancar: 'Cerrar',
    error: 'Ha ocurrido un error',
    previewTitle: 'Previsualización de productos:',
    modifyNote: '* Puedes modificar o eliminar valores antes de importar',
  }
}

const i18n = createI18n({
  legacy: false,
  locale: 'ca',
  fallbackLocale: 'es',
  messages
})

export default i18n

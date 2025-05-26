<template>
  <div class="contact-page">
    <!-- Hero mínim per mantenir consistència -->
    <section class="contact-hero">
      <h1>Contacta amb <span class="highlight">Totaki</span></h1>
      <p>Estem aquí per ajudar-te! Escriu‐nos un missatge i et respondrà un membre de l’equip en un màxim de 24 h.</p>
    </section>

    <!-- Formulari de contacte -->
    <section class="contact-form-section">
      <form @submit.prevent="handleSubmit" class="contact-form">
        <div class="form-group">
          <label for="name">Nom complet</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="El teu nom"
            required
          />
        </div>

        <div class="form-group">
          <label for="email">Correu electrònic</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="exemple@domini.com"
            required
          />
        </div>

        <div class="form-group">
          <label for="subject">Assumpte</label>
          <input
            id="subject"
            v-model="form.subject"
            type="text"
            placeholder="De què vols parlar?"
            required
          />
        </div>

        <div class="form-group">
          <label for="message">Missatge</label>
          <textarea
            id="message"
            v-model="form.message"
            rows="6"
            placeholder="Explica’ns detalladament..."
            required
          ></textarea>
        </div>

        <button type="submit" class="btn-submit" :disabled="submitting">
          {{ submitting ? 'Enviant…' : 'Envia missatge' }}
        </button>
      </form>

      <!-- Informació de contacte alternativa -->
      <aside class="contact-info">
        <h2>Altres maneres de contactar</h2>
        <p><strong>Email:</strong> <a href="mailto:totakistore@gmail.com">totakistore@gmail.com</a></p>
        <p><strong>Twitter:</strong> <a href="https://twitter.com/totakistore" target="_blank">@totakistore</a></p>
        <p><strong>Resposta:</strong> Contestarem en un màxim de 24 h.</p>
      </aside>
    </section>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import axios from 'axios'

const form = reactive({
  name: '',
  email: '',
  subject: '',
  message: ''
})
const submitting = ref(false)

async function handleSubmit() {
  submitting.value = true
  try {
    const res = await axios.post('/contacte', { ...form })
    alert(res.data.message)
    // Netejar
    form.name = form.email = form.subject = form.message = ''
  } catch (err: any) {
    console.error(err)
    alert('Ho sentim, hi ha hagut un error. Torna-ho a provar més tard.')
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.contact-page {
  max-width: 800px;
  margin: 2rem auto;
  padding: 0 1rem;
}
.highlight {
  color: #42b983;
}

/* Hero */
.contact-hero {
  text-align: center;
  margin-bottom: 2rem;
}
.contact-hero h1 {
  font-size: 2.5rem;
  margin: 0;
  color: #064e3b;
}
.contact-hero p {
  margin-top: 0.5rem;
  color: #555;
}

/* Formulari + info */
.contact-form-section {
  display: flex;
  flex-wrap: wrap;
  gap: 2rem;
}

/* Formulari */
.contact-form {
  flex: 1 1 400px;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.form-group {
  display: flex;
  flex-direction: column;
}
.form-group label {
  margin-bottom: 0.25rem;
  font-weight: 500;
  color: #333;
}
.form-group input,
.form-group textarea {
  padding: 0.75rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 1rem;
  resize: vertical;
}
.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #42b983;
  box-shadow: 0 0 0 2px rgba(66,185,131,0.2);
}

.btn-submit {
  align-self: flex-start;
  background: #42b983;
  color: #fff;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-submit:hover:not(:disabled) {
  background: #369e6b;
}

/* Info lateral */
.contact-info {
  flex: 0 0 250px;
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: 4px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.contact-info h2 {
  margin-top: 0;
  color: #064e3b;
}
.contact-info p {
  margin: 0.5rem 0;
}
.contact-info a {
  color: #42b983;
  text-decoration: none;
}
.contact-info a:hover {
  text-decoration: underline;
}

/* Responsive */
@media (max-width: 700px) {
  .contact-form-section {
    flex-direction: column;
  }
}
</style>

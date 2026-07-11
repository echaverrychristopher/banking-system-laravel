<template>
  <div class="auth-container">
    <div class="auth-card">
      <h1>Banking System</h1>
      <p class="subtitle">Iniciar sesión</p>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Correo electrónico</label>
          <input type="email" v-model="email" required placeholder="tu@correo.com" />
        </div>

        <div class="form-group">
          <label>Contraseña</label>
          <input type="password" v-model="password" required placeholder="••••••••" />
        </div>

        <p v-if="error" class="error-message">{{ error }}</p>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Ingresando...' : 'Ingresar' }}
        </button>
      </form>

      <p class="switch-auth">
        ¿No tenés cuenta?
        <router-link to="/register">Registrate acá</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function handleLogin() {
  error.value = '';
  loading.value = true;

  try {
    await authStore.login(email.value, password.value);
    router.push('/dashboard');
  } catch (err) {
    error.value = err.response?.data?.message || 'Credenciales incorrectas';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.auth-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0f172a;
  padding: 1rem;
}

.auth-card {
  background: #1e293b;
  padding: 2.5rem;
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

h1 {
  color: #f8fafc;
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
  text-align: center;
}

.subtitle {
  color: #94a3b8;
  text-align: center;
  margin-bottom: 2rem;
  font-size: 0.9rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

label {
  display: block;
  color: #cbd5e1;
  font-size: 0.85rem;
  margin-bottom: 0.4rem;
}

input {
  width: 100%;
  padding: 0.7rem 0.9rem;
  border-radius: 8px;
  border: 1px solid #334155;
  background: #0f172a;
  color: #f8fafc;
  font-size: 0.95rem;
}

input:focus {
  outline: none;
  border-color: #3b82f6;
}

button {
  width: 100%;
  padding: 0.75rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 0.5rem;
}

button:hover {
  background: #2563eb;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  color: #f87171;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

.switch-auth {
  text-align: center;
  color: #94a3b8;
  font-size: 0.85rem;
  margin-top: 1.5rem;
}

.switch-auth a {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 600;
}
</style>
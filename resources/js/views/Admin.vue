<template>
  <div class="dashboard">
    <nav class="navbar">
      <h1>Banking System — Admin</h1>
      <div class="nav-right">
        <router-link to="/dashboard" class="link-btn">Volver al Dashboard</router-link>
        <span>{{ authStore.user?.name }}</span>
        <button @click="handleLogout" class="logout-btn">Cerrar sesión</button>
      </div>
    </nav>

    <div class="content">
      <div class="tabs">
        <button :class="{ active: activeTab === 'users' }" @click="activeTab = 'users'">Usuarios</button>
        <button :class="{ active: activeTab === 'accounts' }" @click="activeTab = 'accounts'">Todas las Cuentas</button>
      </div>

      <!-- TAB: Usuarios -->
      <div v-if="activeTab === 'users'">
        <h2>Usuarios del Sistema</h2>
        <div v-if="loadingUsers" class="loading">Cargando usuarios...</div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Correo</th>
              <th>Rol</th>
              <th>Registrado</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td>{{ u.name }}</td>
              <td>{{ u.email }}</td>
              <td>
                <span class="role-badge" :class="u.role">{{ u.role }}</span>
              </td>
              <td>{{ formatDate(u.created_at) }}</td>
             <td>
            <select
                v-if="u.id !== authStore.user?.id"
                :value="u.role"
                @change="confirmRoleChange(u, $event.target.value)"
            >
                <option value="cliente">Cliente</option>
                <option value="cajero">Cajero</option>
                <option value="admin">Admin</option>
            </select>
            <span v-else class="self-note">Tu usuario</span>
            </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- TAB: Todas las cuentas -->
      <div v-if="activeTab === 'accounts'">
        <h2>Todas las Cuentas</h2>
        <div v-if="loadingAccounts" class="loading">Cargando cuentas...</div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Número</th>
              <th>Titular</th>
              <th>Tipo</th>
              <th>Balance</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="acc in allAccounts" :key="acc.id">
              <td class="mono">{{ acc.account_number }}</td>
              <td>{{ acc.user?.name }}</td>
              <td>{{ formatType(acc.type) }}</td>
              <td>${{ formatMoney(acc.balance) }}</td>
              <td>
                <span class="status-badge" :class="acc.status">{{ acc.status }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="successMessage" class="success-message">{{ successMessage }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const activeTab = ref('users');
const users = ref([]);
const allAccounts = ref([]);
const loadingUsers = ref(true);
const loadingAccounts = ref(true);
const successMessage = ref('');

async function loadUsers() {
  loadingUsers.value = true;
  try {
    const response = await axios.get('/admin/users');
    users.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loadingUsers.value = false;
  }
}

async function loadAllAccounts() {
  loadingAccounts.value = true;
  try {
    const response = await axios.get('/admin/accounts');
    allAccounts.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loadingAccounts.value = false;
  }
}

function confirmRoleChange(user, newRole) {
  const confirmed = window.confirm(
    `¿Confirmás cambiar el rol de ${user.name} a "${newRole}"?`
  );

  if (confirmed) {
    changeRole(user, newRole);
  } else {
    loadUsers(); // recarga para resetear el <select> a su valor original
  }
}

async function changeRole(user, newRole) {
  try {
    await axios.patch(`/admin/users/${user.id}/role`, { role: newRole });
    successMessage.value = `Rol de ${user.name} actualizado a ${newRole}`;
    setTimeout(() => (successMessage.value = ''), 3000);
    await loadUsers();
  } catch (err) {
    alert(err.response?.data?.message || 'Error al actualizar el rol');
    await loadUsers();
  }
}

function formatType(type) {
  const types = { ahorro: 'Ahorro', corriente: 'Corriente', credito: 'Crédito' };
  return types[type] || type;
}

function formatMoney(amount) {
  return parseFloat(amount).toLocaleString('es-NI', { minimumFractionDigits: 2 });
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('es-NI');
}

function handleLogout() {
  authStore.logout();
  router.push('/login');
}

onMounted(() => {
  authStore.initializeAuth();
  loadUsers();
  loadAllAccounts();
});
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  background: #0f172a;
  color: #f8fafc;
}

.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background: #1e293b;
  border-bottom: 1px solid #334155;
}

.navbar h1 {
  font-size: 1.25rem;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.link-btn {
  color: #3b82f6;
  text-decoration: none;
  font-size: 0.85rem;
}

.logout-btn {
  background: transparent;
  border: 1px solid #475569;
  color: #cbd5e1;
  padding: 0.4rem 0.9rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
}

.content {
  padding: 2rem;
  max-width: 1000px;
  margin: 0 auto;
}

.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.tabs button {
  background: #1e293b;
  border: 1px solid #334155;
  color: #cbd5e1;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  cursor: pointer;
}

.tabs button.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  background: #1e293b;
  border-radius: 12px;
  overflow: hidden;
}

.data-table th {
  text-align: left;
  color: #94a3b8;
  font-size: 0.8rem;
  padding: 0.8rem;
  background: #273449;
}

.data-table td {
  padding: 0.8rem;
  border-top: 1px solid #334155;
  font-size: 0.9rem;
}

.mono {
  font-family: monospace;
}

.role-badge, .status-badge {
  padding: 0.2rem 0.6rem;
  border-radius: 4px;
  font-size: 0.75rem;
  text-transform: capitalize;
}

.role-badge.cliente { background: #1e3a5f; color: #93c5fd; }
.role-badge.cajero { background: #713f12; color: #fde68a; }
.role-badge.admin { background: #14532d; color: #86efac; }

.status-badge.activa { background: #14532d; color: #86efac; }

.data-table select {
  background: #0f172a;
  color: #f8fafc;
  border: 1px solid #334155;
  border-radius: 6px;
  padding: 0.3rem 0.5rem;
}

.loading {
  color: #94a3b8;
  padding: 2rem;
  text-align: center;
}

.success-message {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  background: #14532d;
  color: #86efac;
  padding: 0.8rem 1.2rem;
  border-radius: 8px;
}

.self-note {
  color: #64748b;
  font-size: 0.8rem;
  font-style: italic;
}
</style>
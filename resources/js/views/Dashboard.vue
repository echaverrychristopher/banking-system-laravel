<template>
  <div class="dashboard">
    <nav class="navbar">
      <h1>Banking System</h1>
      <div class="nav-right">
        <span>{{ authStore.user?.name }}</span>
        <button @click="handleLogout" class="logout-btn">Cerrar sesión</button>
      </div>
    </nav>

    <div class="content">
      <div class="header-row">
        <h2>Mis Cuentas</h2>
        <button @click="showCreateModal = true" class="primary-btn">+ Nueva Cuenta</button>
      </div>

      <div v-if="loadingAccounts" class="loading">Cargando cuentas...</div>

      <div v-else-if="accounts.length === 0" class="empty-state">
        <p>Todavía no tenés cuentas. Creá la primera para empezar.</p>
      </div>

      <div v-else class="accounts-grid">
        <div
          v-for="account in accounts"
          :key="account.id"
          class="account-card"
          @click="selectAccount(account)"
        >
          <div class="account-type">{{ formatType(account.type) }}</div>
          <div class="account-number">{{ account.account_number }}</div>
          <div class="account-balance">${{ formatMoney(account.balance) }}</div>
          <div class="account-status" :class="account.status">{{ account.status }}</div>
        </div>
      </div>

      <!-- Detalle de cuenta seleccionada -->
      <div v-if="selectedAccount" class="account-detail">
        <h2>Cuenta {{ selectedAccount.account_number }}</h2>

        <div class="actions-row">
          <button @click="openOperation('deposit')" class="action-btn deposit">Depositar</button>
          <button @click="openOperation('withdraw')" class="action-btn withdraw">Retirar</button>
          <button @click="openOperation('transfer')" class="action-btn transfer">Transferir</button>
        </div>

        <h3>Historial de movimientos</h3>
        <div v-if="!selectedAccount.transactions?.length" class="empty-state">
          Sin movimientos todavía.
        </div>
        <table v-else class="transactions-table">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Monto</th>
              <th>Saldo después</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tx in selectedAccount.transactions" :key="tx.id">
              <td>{{ formatTxType(tx.type) }}</td>
              <td :class="tx.type.includes('recibida') || tx.type === 'deposito' ? 'positive' : 'negative'">
                ${{ formatMoney(tx.amount) }}
              </td>
              <td>${{ formatMoney(tx.balance_after) }}</td>
              <td>{{ formatDate(tx.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal: Crear cuenta -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
      <div class="modal">
        <h3>Nueva Cuenta</h3>
        <select v-model="newAccountType">
          <option value="ahorro">Ahorro</option>
          <option value="corriente">Corriente</option>
          <option value="credito">Crédito</option>
        </select>
        <p v-if="modalError" class="error-message">{{ modalError }}</p>
        <div class="modal-actions">
          <button @click="showCreateModal = false" class="secondary-btn">Cancelar</button>
          <button @click="createAccount" class="primary-btn">Crear</button>
        </div>
      </div>
    </div>

    <!-- Modal: Operación (deposito/retiro/transferencia) -->
    <div v-if="operationType" class="modal-overlay" @click.self="closeOperation">
      <div class="modal">
        <h3>{{ operationTitle }}</h3>

        <label>Monto</label>
        <input type="number" v-model="operationAmount" min="0.01" step="0.01" placeholder="0.00" />

        <template v-if="operationType === 'transfer'">
          <label>Número de cuenta destino</label>
          <input type="text" v-model="transferToAccount" placeholder="Número de cuenta" />
        </template>

        <label>Descripción (opcional)</label>
        <input type="text" v-model="operationDescription" placeholder="Descripción" />

        <p v-if="modalError" class="error-message">{{ modalError }}</p>

        <div class="modal-actions">
          <button @click="closeOperation" class="secondary-btn">Cancelar</button>
          <button @click="submitOperation" :disabled="operationLoading" class="primary-btn">
            {{ operationLoading ? 'Procesando...' : 'Confirmar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const accounts = ref([]);
const loadingAccounts = ref(true);
const selectedAccount = ref(null);

const showCreateModal = ref(false);
const newAccountType = ref('ahorro');
const modalError = ref('');

const operationType = ref(null);
const operationAmount = ref('');
const operationDescription = ref('');
const transferToAccount = ref('');
const operationLoading = ref(false);

const operationTitle = computed(() => {
  if (operationType.value === 'deposit') return 'Depositar dinero';
  if (operationType.value === 'withdraw') return 'Retirar dinero';
  if (operationType.value === 'transfer') return 'Transferir a otra cuenta';
  return '';
});

async function loadAccounts() {
  loadingAccounts.value = true;
  try {
    const response = await axios.get('/accounts');
    accounts.value = response.data;
  } catch (err) {
    console.error(err);
  } finally {
    loadingAccounts.value = false;
  }
}

async function selectAccount(account) {
  const response = await axios.get(`/accounts/${account.id}`);
  selectedAccount.value = response.data;
}

async function createAccount() {
  modalError.value = '';
  try {
    await axios.post('/accounts', { type: newAccountType.value });
    showCreateModal.value = false;
    newAccountType.value = 'ahorro';
    await loadAccounts();
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Error al crear la cuenta';
  }
}

function openOperation(type) {
  operationType.value = type;
  operationAmount.value = '';
  operationDescription.value = '';
  transferToAccount.value = '';
  modalError.value = '';
}

function closeOperation() {
  operationType.value = null;
}

async function submitOperation() {
  modalError.value = '';
  operationLoading.value = true;

  try {
    const payload = {
      amount: parseFloat(operationAmount.value),
      description: operationDescription.value || undefined,
    };

    if (operationType.value === 'transfer') {
      payload.to_account_number = transferToAccount.value;
    }

    await axios.post(`/accounts/${selectedAccount.value.id}/${operationType.value}`, payload);

    closeOperation();
    await loadAccounts();
    await selectAccount(selectedAccount.value);
  } catch (err) {
    const errors = err.response?.data?.errors;
    modalError.value = errors ? Object.values(errors)[0][0] : (err.response?.data?.message || 'Error en la operación');
  } finally {
    operationLoading.value = false;
  }
}

function handleLogout() {
  authStore.logout();
  router.push('/login');
}

function formatType(type) {
  const types = { ahorro: 'Ahorro', corriente: 'Corriente', credito: 'Crédito' };
  return types[type] || type;
}

function formatTxType(type) {
  const types = {
    deposito: 'Depósito',
    retiro: 'Retiro',
    transferencia_enviada: 'Transferencia enviada',
    transferencia_recibida: 'Transferencia recibida',
  };
  return types[type] || type;
}

function formatMoney(amount) {
  return parseFloat(amount).toLocaleString('es-NI', { minimumFractionDigits: 2 });
}

function formatDate(date) {
  return new Date(date).toLocaleString('es-NI');
}

onMounted(() => {
  authStore.initializeAuth();
  loadAccounts();
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

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.primary-btn {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.secondary-btn {
  background: transparent;
  border: 1px solid #475569;
  color: #cbd5e1;
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  cursor: pointer;
}

.accounts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.account-card {
  background: linear-gradient(135deg, #1e293b, #273449);
  padding: 1.5rem;
  border-radius: 12px;
  cursor: pointer;
  border: 1px solid #334155;
  transition: border-color 0.2s;
}

.account-card:hover {
  border-color: #3b82f6;
}

.account-type {
  font-size: 0.8rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.account-number {
  font-size: 1rem;
  color: #cbd5e1;
  margin: 0.5rem 0;
  font-family: monospace;
}

.account-balance {
  font-size: 1.5rem;
  font-weight: 700;
}

.account-status {
  display: inline-block;
  margin-top: 0.5rem;
  padding: 0.2rem 0.6rem;
  border-radius: 4px;
  font-size: 0.75rem;
}

.account-status.activa {
  background: #14532d;
  color: #86efac;
}

.empty-state {
  color: #94a3b8;
  text-align: center;
  padding: 2rem;
}

.loading {
  color: #94a3b8;
  text-align: center;
  padding: 2rem;
}

.account-detail {
  background: #1e293b;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #334155;
}

.actions-row {
  display: flex;
  gap: 0.75rem;
  margin: 1rem 0 1.5rem;
}

.action-btn {
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  color: white;
}

.action-btn.deposit {
  background: #16a34a;
}

.action-btn.withdraw {
  background: #dc2626;
}

.action-btn.transfer {
  background: #3b82f6;
}

.transactions-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

.transactions-table th {
  text-align: left;
  color: #94a3b8;
  font-size: 0.8rem;
  padding: 0.6rem;
  border-bottom: 1px solid #334155;
}

.transactions-table td {
  padding: 0.6rem;
  border-bottom: 1px solid #1e293b;
  font-size: 0.9rem;
}

.positive {
  color: #86efac;
}

.negative {
  color: #fca5a5;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.modal {
  background: #1e293b;
  padding: 2rem;
  border-radius: 12px;
  width: 100%;
  max-width: 380px;
}

.modal h3 {
  margin-bottom: 1rem;
}

.modal select,
.modal input {
  width: 100%;
  padding: 0.6rem 0.8rem;
  border-radius: 8px;
  border: 1px solid #334155;
  background: #0f172a;
  color: #f8fafc;
  margin-bottom: 1rem;
}

.modal label {
  display: block;
  font-size: 0.85rem;
  color: #cbd5e1;
  margin-bottom: 0.3rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.error-message {
  color: #f87171;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}
</style>
import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
    },

    actions: {
        async login(email, password) {
            const response = await axios.post('/login', { email, password });
            this.setAuth(response.data.user, response.data.token);
        },

        async register(name, email, password, password_confirmation) {
            const response = await axios.post('/register', {
                name,
                email,
                password,
                password_confirmation,
            });
            this.setAuth(response.data.user, response.data.token);
        },

        setAuth(user, token) {
            this.user = user;
            this.token = token;
            localStorage.setItem('user', JSON.stringify(user));
            localStorage.setItem('token', token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        },

        async logout() {
            try {
                await axios.post('/logout');
            } finally {
                this.user = null;
                this.token = null;
                localStorage.removeItem('user');
                localStorage.removeItem('token');
                delete axios.defaults.headers.common['Authorization'];
            }
        },

        initializeAuth() {
            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
            }
        },
    },
});
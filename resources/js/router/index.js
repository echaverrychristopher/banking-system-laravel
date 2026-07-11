import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/Login.vue'),
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../views/Register.vue'),
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('../views/Dashboard.vue'),
        meta: { requiresAuth: true },
    },

    {
        path: '/admin',
        name: 'admin',
        component: () => import('../views/Admin.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    }, 
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
    } else if (to.meta.requiresAdmin && authStore.user?.role !== 'admin') {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
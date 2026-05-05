import { createRouter, createWebHistory } from 'vue-router';
import { getProfile } from '../services/authProfile';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: () => import('../views/HomeView.vue') },
        { path: '/properties', name: 'properties', component: () => import('../views/PropertiesListView.vue') },
        { path: '/properties/:id', name: 'property-detail', component: () => import('../views/PropertyDetailView.vue') },
        {
            path: '/properties/new',
            name: 'property-create',
            component: () => import('../views/PropertyFormView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/properties/:id/edit',
            name: 'property-edit',
            component: () => import('../views/PropertyFormView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/messages',
            name: 'messages',
            component: () => import('../views/MessagesInboxView.vue'),
            meta: { requiresAuth: true },
        },
        { path: '/login', name: 'login', component: () => import('../views/LoginView.vue') },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: () => import('../views/DashboardView.vue'),
            meta: { requiresAuth: true },
        },
    ],
});

router.beforeEach(async (to) => {
    if (!to.meta.requiresAuth) {
        return true;
    }

    const profile = await getProfile();
    if (!profile) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    return true;
});

export default router;

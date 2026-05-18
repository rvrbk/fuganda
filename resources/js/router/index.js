import { createRouter, createWebHistory } from 'vue-router';
import { canManageListings, getProfile } from '../services/authProfile';
import { hasActiveSellerSubscription } from '../services/sellerBilling';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: () => import('../views/PropertiesListView.vue') },
        { path: '/properties/:id', name: 'property-detail', component: () => import('../views/PropertyDetailView.vue') },
        {
            path: '/properties/new',
            name: 'property-create',
            component: () => import('../views/PropertyFormView.vue'),
            meta: { requiresAuth: true, requiresSellerRole: true },
        },
        {
            path: '/properties/:id/edit',
            name: 'property-edit',
            component: () => import('../views/PropertyFormView.vue'),
            meta: { requiresAuth: true, requiresSellerRole: true },
        },
        {
            path: '/messages',
            name: 'messages',
            component: () => import('../views/MessagesInboxView.vue'),
            meta: { requiresAuth: true },
        },
        { path: '/login', name: 'login', component: () => import('../views/LoginView.vue') },
        { path: '/forgot-password', name: 'forgot-password', component: () => import('../views/ForgotPasswordView.vue') },
        { path: '/reset-password/:token', name: 'reset-password', component: () => import('../views/ResetPasswordView.vue') },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: () => import('../views/DashboardView.vue'),
            meta: { requiresAuth: true, requiresSellerRole: true },
        },
        {
            path: '/seller/onboarding',
            name: 'seller-onboarding',
            component: () => import('../views/SellerOnboardingView.vue'),
            meta: { requiresAuth: true, requiresSellerRole: true },
        },
        { path: '/forbidden', name: 'forbidden', component: () => import('../views/ForbiddenView.vue') },
    ],
});

router.beforeEach(async (to) => {
    try {
        if (!to.meta.requiresAuth) {
            return true;
        }

        let profile = null;
        try {
            profile = await getProfile();
        } catch {
            profile = null;
        }

        if (!profile) {
            return { name: 'login', query: { redirect: to.fullPath } };
        }

        if (to.meta.requiresSellerRole && !canManageListings(profile)) {
            return { name: 'forbidden', query: { from: to.fullPath } };
        }

        if (canManageListings(profile)) {
            let hasActiveSubscription = false;
            try {
                hasActiveSubscription = await hasActiveSellerSubscription();
            } catch {
                // Gracefully degrade to onboarding instead of breaking navigation.
                hasActiveSubscription = false;
            }

            const isOnboardingRoute = to.name === 'seller-onboarding';

            if (!hasActiveSubscription && !isOnboardingRoute) {
                return { name: 'seller-onboarding', query: { redirect: to.fullPath } };
            }

            if (hasActiveSubscription && isOnboardingRoute) {
                return { name: 'dashboard' };
            }
        }

        return true;
    } catch {
        return { name: 'home' };
    }
});

router.onError((error, to) => {
    const message = String(error?.message ?? '');
    const isChunkLoadFailure = /failed to fetch dynamically imported module|importing a module script failed|loading chunk [\w-]+ failed|dynamically imported module/i.test(message);

    if (!isChunkLoadFailure) {
        return;
    }

    const reloadKey = 'router:chunk-reload';
    if (window.sessionStorage.getItem(reloadKey)) {
        window.sessionStorage.removeItem(reloadKey);
        return;
    }

    window.sessionStorage.setItem(reloadKey, '1');
    window.location.assign(to?.fullPath || window.location.href);
});

export default router;

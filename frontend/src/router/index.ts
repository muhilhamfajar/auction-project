import { createRouter, createWebHistory, RouteRecordRaw, NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { authGuard } from '@/utils/authGuard'

type CustomRouteRecordRaw = RouteRecordRaw & {
  meta: {
    layout: 'auth' | 'guest'
    requiresAuth: boolean
    guest?: boolean
    adminOnly?: boolean
    userOnly?: boolean
  }
}

const routes: CustomRouteRecordRaw[] = [
  {
    path: '/',
    name: 'index',
    component: () => import('../pages/index.vue'),
    meta: { layout: 'auth', requiresAuth: true, userOnly: true }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../pages/dashboard.vue'),
    meta: { layout: 'auth', requiresAuth: true, adminOnly: true }
  },
  {
    path: '/account',
    name: 'account',
    component: () => import('../pages/account/index.vue'),
    meta: { layout: 'auth', requiresAuth: true, userOnly: true }
  },
  {
    path: '/account/auto-bid',
    name: 'accountAutoBidConfig',
    component: () => import('../pages/account/autoBidConfig.vue'),
    meta: { layout: 'auth', requiresAuth: true, userOnly: true }
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/login.vue'),
    meta: { layout: 'guest', requiresAuth: false, guest: true }
  },
  {
    path: '/items/add',
    name: 'itemsAdd',
    component: () => import('../pages/items/add.vue'),
    meta: { layout: 'auth', requiresAuth: false, adminOnly: true }
  },
  {
    path: '/items/:uuid',
    name: 'detailItem',
    component: () => import('../pages/items/[id]/index.vue'),
    meta: { layout: 'auth', requiresAuth: true }
  },
  {
    path: '/items/:uuid/edit',
    name: 'editItem',
    component: () => import('../pages/items/[id]/edit.vue'),
    meta: { layout: 'auth', requiresAuth: true, adminOnly: true }
  },
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('../pages/notifications.vue'),
    meta: { layout: 'auth', requiresAuth: true, userOnly: true }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('../pages/[...all].vue'),
    meta: { layout: 'guest', requiresAuth: false }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach(authGuard)

window.addEventListener('auth:required', () => {
  router.push('/login')
})


export default router
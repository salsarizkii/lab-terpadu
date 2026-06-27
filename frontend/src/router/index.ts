import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/RegisterView.vue'),
    meta: { guest: true },
  },
  {
    path: '/dashboard',
    redirect: () => {
      const auth = useAuthStore()
      if (auth.isSuperAdmin || auth.isAdmin) return '/admin/dashboard'
      if (auth.isAnalyst) return '/analyst/dashboard'
      return '/user/dashboard'
    },
  },
  {
    path: '/user/dashboard',
    name: 'UserDashboard',
    component: () => import('../views/UserDashboard.vue'),
    meta: { auth: true, role: 'user' },
  },
  {
    path: '/user/berita-acara',
    name: 'UserBeritaAcaraList',
    component: () => import('../views/UserBeritaAcaraList.vue'),
    meta: { auth: true, role: 'user' },
  },
  {
    path: '/user/berita-acara/create',
    name: 'UserBeritaAcaraCreate',
    component: () => import('../views/UserBeritaAcaraCreate.vue'),
    meta: { auth: true, role: 'user' },
  },
  {
    path: '/user/berita-acara/:id',
    name: 'UserBeritaAcaraDetail',
    component: () => import('../views/UserBeritaAcaraDetail.vue'),
    meta: { auth: true, role: 'user' },
  },
  {
    path: '/user/payments',
    name: 'UserPayment',
    component: () => import('../views/UserPayment.vue'),
    meta: { auth: true, role: 'user' },
  },
  {
    path: '/user/certificates',
    name: 'UserCertificate',
    component: () => import('../views/UserCertificate.vue'),
    meta: { auth: true, role: 'user' },
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: () => import('../views/AdminDashboard.vue'),
    meta: { auth: true, role: 'admin' },
  },
  {
    path: '/admin/berita-acara',
    name: 'AdminBeritaAcara',
    component: () => import('../views/AdminBeritaAcara.vue'),
    meta: { auth: true, role: 'admin' },
  },
  {
    path: '/admin/payments',
    name: 'AdminPayments',
    component: () => import('../views/AdminPayments.vue'),
    meta: { auth: true, role: 'admin' },
  },
  {
    path: '/admin/samples',
    name: 'AdminSamples',
    component: () => import('../views/AdminSamples.vue'),
    meta: { auth: true, role: 'admin' },
  },
  {
    path: '/admin/roles',
    name: 'AdminRoles',
    component: () => import('../views/AdminRoles.vue'),
    meta: { auth: true, role: 'superadmin' },
  },
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: () => import('../views/AdminUsers.vue'),
    meta: { auth: true, role: 'superadmin' },
  },
  {
    path: '/admin/forms',
    name: 'FormTemplateEditor',
    component: () => import('../views/FormTemplateEditor.vue'),
    meta: { auth: true, role: 'admin' },
  },
  {
    path: '/analyst/dashboard',
    name: 'AnalystDashboard',
    component: () => import('../views/AnalystDashboard.vue'),
    meta: { auth: true, role: 'analyst' },
  },
  {
    path: '/analyst/assigned',
    name: 'AnalystAssigned',
    component: () => import('../views/AnalystAssigned.vue'),
    meta: { auth: true, role: 'analyst' },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()

  if (to.meta.auth && !auth.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return next('/dashboard')
  }

  if (to.meta.role && auth.user) {
    const requiredRole = to.meta.role as string
    const userRole = auth.user.role

    // Superadmin can access admin and superadmin routes
    if (userRole === 'superadmin' && (requiredRole === 'admin' || requiredRole === 'superadmin')) {
      return next()
    }

    // Check exact role match
    if (userRole !== requiredRole) {
      return next('/dashboard')
    }
  }

  next()
})

export default router

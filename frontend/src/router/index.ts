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
      if (!auth.hasPermission('dashboard')) {
        if (auth.hasPermission('berita_acara_create') && auth.isUser) return '/user/berita-acara'
        if (auth.hasPermission('pengujian_input')) return '/analyst/assigned'
        if (auth.hasPermission('berita_acara_view')) return '/admin/berita-acara'
        if (auth.hasPermission('sample_view')) return '/admin/samples'
        return '/login'
      }
      // Permission-based dashboard redirect
      if (auth.isAdmin || auth.isSuperAdmin) return '/admin/dashboard'
      if (auth.isAnalyst) return '/analyst/dashboard'
      // Custom roles: cek permission lain untuk tentukan dashboard
      if (auth.hasPermission('sample_view') || auth.hasPermission('pengujian_input')) return '/analyst/dashboard'
      return '/user/dashboard'
    },
  },
  {
    path: '/user/dashboard',
    name: 'UserDashboard',
    component: () => import('../views/UserDashboard.vue'),
    meta: { auth: true, permission: 'dashboard' },
  },
  {
    path: '/user/berita-acara',
    name: 'UserBeritaAcaraList',
    component: () => import('../views/UserBeritaAcaraList.vue'),
    meta: { auth: true },
  },
  {
    path: '/user/berita-acara/create',
    name: 'UserBeritaAcaraCreate',
    component: () => import('../views/UserBeritaAcaraCreate.vue'),
    meta: { auth: true },
  },
  {
    path: '/user/berita-acara/:id',
    name: 'UserBeritaAcaraDetail',
    component: () => import('../views/UserBeritaAcaraDetail.vue'),
    meta: { auth: true },
  },
  {
    path: '/user/payments',
    name: 'UserPayment',
    component: () => import('../views/UserPayment.vue'),
    meta: { auth: true },
  },
  {
    path: '/user/certificates',
    name: 'UserCertificate',
    component: () => import('../views/UserCertificate.vue'),
    meta: { auth: true },
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: () => import('../views/AdminDashboard.vue'),
    meta: { auth: true, permission: 'dashboard' },
  },
  {
    path: '/admin/berita-acara',
    name: 'AdminBeritaAcara',
    component: () => import('../views/AdminBeritaAcara.vue'),
    meta: { auth: true, permission: 'berita_acara_view' },
  },
  {
    path: '/admin/payments',
    name: 'AdminPayments',
    component: () => import('../views/AdminPayments.vue'),
    meta: { auth: true, permission: 'payment_view' },
  },
  {
    path: '/admin/samples',
    name: 'AdminSamples',
    component: () => import('../views/AdminSamples.vue'),
    meta: { auth: true, permission: 'sample_view', permissionOr: 'pengujian_input' },
  },
  {
    path: '/admin/roles',
    name: 'AdminRoles',
    component: () => import('../views/AdminRoles.vue'),
    meta: { auth: true, permission: 'role_manage' },
  },
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: () => import('../views/AdminUsers.vue'),
    meta: { auth: true, permission: 'user_view' },
  },
  {
    path: '/admin/forms',
    name: 'FormTemplateEditor',
    component: () => import('../views/FormTemplateEditor.vue'),
    meta: { auth: true, permission: 'form_manage_berita_acara', permissionOr: 'form_manage_rekap_analis' },
  },
  {
    path: '/analyst/dashboard',
    name: 'AnalystDashboard',
    component: () => import('../views/AnalystDashboard.vue'),
    meta: { auth: true, permission: 'dashboard' },
  },
  {
    path: '/analyst/assigned',
    name: 'AnalystAssigned',
    component: () => import('../views/AnalystAssigned.vue'),
    meta: { auth: true, permission: 'pengujian_input' },
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

  // Permission check — supports permission OR permissionOr
  if (auth.user) {
    const requiredPermission = to.meta.permission as string | undefined
    const altPermission = to.meta.permissionOr as string | undefined

    if (requiredPermission) {
      const hasMain = auth.hasPermission(requiredPermission)
      const hasAlt = altPermission ? auth.hasPermission(altPermission) : false

      if (!hasMain && !hasAlt) {
        return next('/dashboard')
      }
    }
  }

  next()
})

export default router

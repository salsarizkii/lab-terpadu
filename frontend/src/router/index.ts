import { createRouter, createWebHistory } from 'vue-router'
import { pinia } from '../stores/pinia'
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
      const auth = useAuthStore(pinia)
      if (!auth.hasPermission('dashboard')) {
        // Fallback: arahkan ke halaman sesuai permission pertama yang dimiliki
        if (auth.hasPermission('berita_acara_riwayat_semua') || auth.hasPermission('berita_acara_verify') || auth.hasPermission('berita_acara_assign')) return '/admin/berita-acara'
        if (auth.hasPermission('berita_acara_create') || auth.hasPermission('berita_acara_view')) return '/user/berita-acara'
        if (auth.hasPermission('sample_view')) return '/admin/samples'
        return '/login'
      }
      if (auth.isAdmin || auth.isSuperAdmin) return '/admin/dashboard'
      if (auth.isAnalyst) return '/analyst/dashboard'
      if (auth.hasPermission('sample_view') || auth.hasPermission('sample_rekap')) return '/analyst/dashboard'
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
    path: '/user/berita-acara/:id/edit',
    name: 'UserBeritaAcaraEdit',
    component: () => import('../views/UserBeritaAcaraEdit.vue'),
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
    meta: { auth: true, permission: 'berita_acara_view', permissionOr: 'berita_acara_riwayat_semua,berita_acara_riwayat_personal,berita_acara_verify,berita_acara_assign,berita_acara_create' },
  },
  {
    path: '/admin/berita-acara/:id',
    name: 'AdminBeritaAcaraDetail',
    component: () => import('../views/AdminBeritaAcaraDetail.vue'),
    meta: { auth: true, permission: 'berita_acara_view', permissionOr: 'berita_acara_riwayat_semua,berita_acara_riwayat_personal,berita_acara_verify,berita_acara_assign' },
  },
  {
    path: '/admin/payments',
    name: 'AdminPayments',
    component: () => import('../views/AdminPayments.vue'),
    meta: { auth: true, permission: 'payment_view', permissionOr: 'payment_upload,payment_confirm,payment_discount' },
  },
  {
    path: '/admin/samples',
    name: 'AdminSamples',
    component: () => import('../views/AdminSamples.vue'),
    meta: { auth: true, permission: 'sample_view', permissionOr: 'sample_rekap,sample_riwayat_personal,sample_riwayat_semua,sample_input,sample_status' },
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
    meta: { auth: true, permission: 'form_manage_berita_acara', permissionOr: 'form_manage_rekap_analis,form_manage_perhitungan_biaya,form_manage_kelola_harga_instrumen' },
  },
  {
    path: '/analyst/dashboard',
    name: 'AnalystDashboard',
    component: () => import('../views/AnalystDashboard.vue'),
    meta: { auth: true, permission: 'dashboard' },
  },
  {
    path: '/analyst/samples',
    name: 'AnalystSamples',
    component: () => import('../views/AnalystSamples.vue'),
    meta: { auth: true, permission: 'sample_rekap', permissionOr: 'berita_acara_riwayat_semua' },
  },
  {
    path: '/analyst/samples/:id',
    name: 'AnalystSampleDetail',
    component: () => import('../views/AnalystAssigned.vue'),
    meta: { auth: true, permission: 'sample_rekap', permissionOr: 'berita_acara_riwayat_semua' },
  },
  {
    path: '/analyst/samples/:id/rekap',
    name: 'AnalystRekapAnalis',
    component: () => import('../views/AnalystRekapAnalis.vue'),
    meta: { auth: true, permission: 'sample_rekap', permissionOr: 'berita_acara_riwayat_semua' },
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
  const auth = useAuthStore(pinia)

  if (to.meta.auth && !auth.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return next('/dashboard')
  }

  // Permission check — supports permission OR permissionOr (comma-separated)
  if (auth.user) {
    const requiredPermission = to.meta.permission as string | undefined
    const altPermissions = to.meta.permissionOr as string | undefined

    if (requiredPermission) {
      const hasMain = auth.hasPermission(requiredPermission)
      let hasAlt = false
      if (altPermissions) {
        for (const alt of altPermissions.split(',')) {
          if (auth.hasPermission(alt.trim())) {
            hasAlt = true
            break
          }
        }
      }

      if (!hasMain && !hasAlt) {
        return next('/dashboard')
      }
    }
  }

  next()
})

export default router

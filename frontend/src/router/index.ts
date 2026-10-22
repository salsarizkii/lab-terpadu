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
      if (auth.effectiveRole === 'penyelia') return '/penyelia/dashboard'
      if (auth.effectiveRole === 'manager_teknis') return '/manager-teknis/dashboard'
      if (!auth.hasPermission('dashboard')) {
        if (auth.hasPermission('berita_acara_riwayat_semua') || auth.hasPermission('berita_acara_verify') || auth.hasPermission('berita_acara_assign')) return '/admin/berita-acara'
        if (auth.hasPermission('berita_acara_create') || auth.hasPermission('berita_acara_riwayat_personal') || auth.hasPermission('berita_acara_riwayat_semua') || auth.hasPermission('berita_acara_verify') || auth.hasPermission('berita_acara_assign')) return '/user/berita-acara'
        if (auth.hasPermission('hasil_pengujian_rekap') || auth.hasPermission('hasil_pengujian_riwayat_semua')) return '/admin/samples'
        if (auth.hasPermission('hasil_pengujian_riwayat_personal')) return '/user/samples'
        if (auth.hasPermission('payment_upload') || auth.hasPermission('payment_discount') || auth.hasPermission('payment_riwayat_personal')) return '/user/payments'
        if (auth.hasPermission('certificate_view') || auth.hasPermission('certificate_download')) return '/user/certificates'
        if (auth.hasPermission('user_view')) return '/admin/users'
        return '/user/berita-acara'
      }
      if (auth.hasPermission('berita_acara_riwayat_semua') || auth.hasPermission('hasil_pengujian_riwayat_semua') || auth.hasPermission('payment_riwayat_semua') || auth.hasPermission('user_view')) return '/admin/dashboard'
      if (auth.hasPermission('hasil_pengujian_rekap')) return '/analyst/dashboard'
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
    meta: { auth: true, permission: 'berita_acara_create', permissionOr: 'berita_acara_riwayat_personal' },
  },
  {
    path: '/user/berita-acara/create',
    name: 'UserBeritaAcaraCreate',
    component: () => import('../views/UserBeritaAcaraCreate.vue'),
    meta: { auth: true, permission: 'berita_acara_create' },
  },
  {
    path: '/user/berita-acara/:id',
    name: 'UserBeritaAcaraDetail',
    component: () => import('../views/UserBeritaAcaraDetail.vue'),
    meta: { auth: true, permission: 'berita_acara_create', permissionOr: 'berita_acara_riwayat_personal' },
  },
  {
    path: '/user/berita-acara/:id/edit',
    name: 'UserBeritaAcaraEdit',
    component: () => import('../views/UserBeritaAcaraEdit.vue'),
    meta: { auth: true, permission: 'berita_acara_create' },
  },
  {
    path: '/user/payments',
    name: 'UserPayment',
    component: () => import('../views/UserPayment.vue'),
    meta: { auth: true, permission: 'payment_upload', permissionOr: 'payment_discount,payment_riwayat_personal' },
  },
  {
    path: '/user/payments/:id',
    name: 'UserPaymentDetail',
    component: () => import('../views/UserPaymentDetail.vue'),
    meta: { auth: true, permission: 'payment_upload', permissionOr: 'payment_discount,payment_riwayat_personal' },
  },
  {
    path: '/user/samples',
    name: 'UserSamples',
    component: () => import('../views/UserSamples.vue'),
    meta: { auth: true, permission: 'hasil_pengujian_riwayat_personal' },
  },
  {
    path: '/user/certificates',
    name: 'UserCertificate',
    component: () => import('../views/UserCertificate.vue'),
    meta: { auth: true, permission: 'certificate_view', permissionOr: 'certificate_download' },
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
    meta: { auth: true, permission: 'berita_acara_riwayat_semua', permissionOr: 'berita_acara_riwayat_personal,berita_acara_verify,berita_acara_assign,berita_acara_create' },
  },
  {
    path: '/admin/berita-acara/:id',
    name: 'AdminBeritaAcaraDetail',
    component: () => import('../views/AdminBeritaAcaraDetail.vue'),
    meta: { auth: true, permission: 'berita_acara_riwayat_semua', permissionOr: 'berita_acara_riwayat_personal,berita_acara_verify,berita_acara_assign' },
  },
  {
    path: '/admin/payments',
    name: 'AdminPayments',
    component: () => import('../views/AdminPayments.vue'),
    meta: { auth: true, permission: 'payment_riwayat_semua' },
  },
  {
    path: '/admin/samples',
    name: 'AdminSamples',
    component: () => import('../views/AdminSamples.vue'),
    meta: { auth: true, permission: 'hasil_pengujian_rekap', permissionOr: 'hasil_pengujian_riwayat_personal,hasil_pengujian_riwayat_semua' },
  },
  {
    path: '/admin/roles',
    name: 'AdminRoles',
    component: () => import('../views/AdminRoles.vue'),
    meta: { auth: true, superadminOnly: true },
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
    path: '/admin/discounts',
    name: 'AdminDiscounts',
    component: () => import('../views/AdminDiscounts.vue'),
    meta: { auth: true, permission: 'discount_verifikasi', permissionOr: 'discount_riwayat_semua,discount_riwayat_personal' },
  },
  {
    path: '/admin/activity-log',
    name: 'AdminSystemActivityLog',
    component: () => import('../views/AdminSystemActivityLog.vue'),
    meta: { auth: true, permission: 'user_view' },
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
    meta: { auth: true, permission: 'hasil_pengujian_rekap', permissionOr: 'berita_acara_riwayat_semua' },
  },
  {
    path: '/analyst/samples/:id',
    name: 'AnalystSampleDetail',
    component: () => import('../views/AnalystAssigned.vue'),
    meta: { auth: true, permission: 'hasil_pengujian_rekap', permissionOr: 'berita_acara_riwayat_semua' },
  },
  {
    path: '/analyst/samples/:id/rekap',
    name: 'AnalystRekapAnalis',
    component: () => import('../views/AnalystRekapAnalis.vue'),
    meta: { auth: true, permission: 'hasil_pengujian_rekap', permissionOr: 'berita_acara_riwayat_semua' },
  },
  {
    path: '/penyelia/dashboard',
    name: 'PenyeliaDashboard',
    component: () => import('../views/PenyeliaDashboard.vue'),
    meta: { auth: true, permission: 'berita_acara_verify' },
  },
  {
    path: '/penyelia/berita-acara',
    name: 'PenyeliaBeritaAcara',
    component: () => import('../views/PenyeliaBeritaAcara.vue'),
    meta: { auth: true, permission: 'berita_acara_verify' },
  },
  {
    path: '/penyelia/berita-acara/:id',
    name: 'PenyeliaBeritaAcaraDetail',
    component: () => import('../views/PenyeliaBeritaAcaraDetail.vue'),
    meta: { auth: true, permission: 'berita_acara_verify' },
  },
  {
    path: '/manager-teknis/dashboard',
    name: 'ManagerDashboard',
    component: () => import('../views/ManagerDashboard.vue'),
    meta: { auth: true, permission: 'hasil_pengujian_rekap' },
  },
  {
    path: '/manager-teknis/berita-acara',
    name: 'ManagerBeritaAcara',
    component: () => import('../views/ManagerBeritaAcara.vue'),
    meta: { auth: true, permission: 'hasil_pengujian_rekap' },
  },
  {
    path: '/manager-teknis/berita-acara/:id',
    name: 'ManagerBeritaAcaraDetail',
    component: () => import('../views/ManagerBeritaAcaraDetail.vue'),
    meta: { auth: true, permission: 'hasil_pengujian_rekap' },
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

  // Route khusus superadmin (mis. kelola role)
  if (to.meta.superadminOnly && !auth.isSuperAdmin) {
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

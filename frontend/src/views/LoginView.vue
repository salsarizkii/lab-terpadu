<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { toast } from 'vue-sonner'
import LoginForm from '../components/auth/LoginForm.vue'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const loading = ref(false)

onMounted(() => {
  if (route.query.registered === 'true') {
    if (route.query.needs_verification === '1') {
      toast.success('Registrasi berhasil! Akun Anda memerlukan verifikasi dari superadmin sebelum dapat login.')
    } else {
      toast.success('Registrasi berhasil! Silakan login dengan akun Anda.')
    }
  }
})

async function handleLogin(data: { email: string; password: string }) {
  loading.value = true
  try {
    await auth.login(data.email, data.password)
    toast.success('Login berhasil')
    router.push('/dashboard')
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Login gagal')
  } finally {
    loading.value = false
  }
}

function handleForgotPassword() {
  toast.info('Hubungi superadmin untuk reset password Anda.')
}
</script>

<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-header">
        <h1>UPT Lab Terpadu</h1>
        <p>Pemesanan Data Hasil Pengujian Penelitian</p>
      </div>
      <LoginForm @submit="handleLogin" :loading="loading" @forgot-password="handleForgotPassword" />
      <p class="login-footer">
        Belum punya akun? <router-link to="/register">Daftar</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%);
}
.login-card {
  background: white;
  border-radius: 12px;
  padding: 40px;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}
.login-header {
  text-align: center;
  margin-bottom: 32px;
}
.login-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1e3a5f;
  margin: 0 0 4px;
}
.login-header p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}
.login-success-banner {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 14px 16px;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 13px;
  color: #065f46;
  line-height: 1.5;
}
.login-success-icon {
  flex-shrink: 0;
  margin-top: 1px;
}
.login-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 14px;
  color: #64748b;
}
.login-footer a {
  color: #2d6a9f;
  text-decoration: none;
  font-weight: 500;
}
</style>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import { toast } from 'vue-sonner'
import RegisterForm from '../components/auth/RegisterForm.vue'
import { extractApiError } from '../utils/errorMessage'

const router = useRouter()
const auth = useAuthStore()
const loading = ref(false)
const error = ref('')

async function handleRegister(data: { name: string; email: string; password: string; password_confirmation: string; phone?: string; role: string }) {
  error.value = ''
  loading.value = true
  try {
    const res = await auth.register(data)
    const needsVerification = res.needs_verification ? '1' : '0'
    router.push(`/login?registered=true&needs_verification=${needsVerification}`)
  } catch (e: any) {
    error.value = extractApiError(e, 'Registrasi gagal')
    toast.error(error.value)
  } finally {
    loading.value = false
  }
}

</script>

<template>
  <div class="register-page">
    <div class="register-card">
      <div class="register-header">
        <h1>Daftar Akun</h1>
        <p>UPT Lab Terpadu - Pemesanan Data Hasil Pengujian</p>
      </div>
      <RegisterForm @submit="handleRegister" :loading="loading" :error="error" />
      <p class="register-footer">
        Sudah punya akun? <router-link to="/login">Masuk</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.register-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%);
}
.register-card {
  background: white;
  border-radius: 12px;
  padding: 40px;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}
.register-header {
  text-align: center;
  margin-bottom: 32px;
}
.register-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1e3a5f;
  margin: 0 0 4px;
}
.register-header p {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}
.register-footer {
  text-align: center;
  margin-top: 24px;
  font-size: 14px;
  color: #64748b;
}
.register-footer a {
  color: #2d6a9f;
  text-decoration: none;
  font-weight: 500;
}
</style>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import LoginForm from '../components/auth/LoginForm.vue'
import Alert from '../components/common/Alert.vue'

const router = useRouter()
const auth = useAuthStore()
const error = ref('')
const loading = ref(false)

async function handleLogin(data: { email: string; password: string }) {
  error.value = ''
  loading.value = true
  try {
    await auth.login(data.email, data.password)
    router.push('/dashboard')
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Login gagal'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-header">
        <h1>Lab Terpadu</h1>
        <p>Pemesanan Data Sampel Penelitian</p>
      </div>
      <Alert v-if="error" type="error" :message="error" @dismiss="error = ''" />
      <LoginForm @submit="handleLogin" :loading="loading" />
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

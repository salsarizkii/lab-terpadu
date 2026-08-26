<script setup lang="ts">
import { ref } from 'vue'
import FormInput from '../common/FormInput.vue'
import Button from '../common/Button.vue'

withDefaults(defineProps<{
  loading?: boolean
  error?: string
}>(), {
  error: '',
})

const emit = defineEmits<{
  submit: [data: { email: string; password: string }]
  'forgot-password': []
}>()

const email = ref('')
const password = ref('')

function handleSubmit() {
  emit('submit', { email: email.value, password: password.value })
}
</script>

<template>
  <form class="auth-form" @submit.prevent="handleSubmit">
    <div v-if="error" class="form-alert" role="alert">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="form-alert__icon"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
      <span>{{ error }}</span>
    </div>
    <FormInput v-model="email" label="Email" type="email" placeholder="contoh@email.com" required autocomplete="email" />
    <FormInput v-model="password" label="Password" type="password" placeholder="Masukkan password" required autocomplete="current-password" />
    <div class="form-options">
      <a href="#" class="forgot-link" @click.prevent="$emit('forgot-password')">Lupa password?</a>
    </div>
    <Button variant="primary" size="lg" block :loading="loading" type="submit">
      Masuk
    </Button>
  </form>
</template>

<style scoped>
.auth-form {
  display: flex;
  flex-direction: column;
}

.form-alert {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 10px 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  font-size: 13px;
  color: #b91c1c;
  line-height: 1.4;
  margin-bottom: 16px;
}
.form-alert__icon {
  flex-shrink: 0;
  margin-top: 1px;
}

.form-options {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 16px;
}

.forgot-link {
  font-size: 13px;
  color: #2d6a9f;
  text-decoration: none;
  font-weight: 500;
}

.forgot-link:hover {
  text-decoration: underline;
}
</style>
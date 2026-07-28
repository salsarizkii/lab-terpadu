<script setup lang="ts">
import { ref } from 'vue'
import FormInput from '../common/FormInput.vue'
import Button from '../common/Button.vue'

defineProps<{
  loading?: boolean
}>()

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

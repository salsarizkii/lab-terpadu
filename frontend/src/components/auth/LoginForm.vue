<script setup lang="ts">
import { ref } from 'vue'
import FormInput from '../common/FormInput.vue'
import Button from '../common/Button.vue'

defineProps<{
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [data: { email: string; password: string }]
}>()

const email = ref('')
const password = ref('')

function handleSubmit() {
  emit('submit', { email: email.value, password: password.value })
}
</script>

<template>
  <form class="auth-form" @submit.prevent="handleSubmit">
    <FormInput v-model="email" label="Email" type="email" placeholder="contoh@email.com" required />
    <FormInput v-model="password" label="Password" type="password" placeholder="Masukkan password" required />
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
</style>

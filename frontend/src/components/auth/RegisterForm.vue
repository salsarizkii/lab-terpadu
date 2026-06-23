<script setup lang="ts">
import { ref } from 'vue'
import FormInput from '../common/FormInput.vue'
import Button from '../common/Button.vue'

defineProps<{
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [data: {
    name: string
    email: string
    password: string
    password_confirmation: string
    phone?: string
  }]
}>()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const phone = ref('')

function handleSubmit() {
  emit('submit', {
    name: name.value,
    email: email.value,
    password: password.value,
    password_confirmation: passwordConfirmation.value,
    phone: phone.value || undefined,
  })
}
</script>

<template>
  <form class="auth-form" @submit.prevent="handleSubmit">
    <FormInput v-model="name" label="Nama Lengkap" placeholder="Nama lengkap" required />
    <FormInput v-model="email" label="Email" type="email" placeholder="contoh@email.com" required />
    <FormInput v-model="password" label="Password" type="password" placeholder="Min. 8 karakter" required />
    <FormInput v-model="passwordConfirmation" label="Konfirmasi Password" type="password" placeholder="Ulangi password" required />
    <FormInput v-model="phone" label="No. Telepon (opsional)" type="tel" placeholder="08xxxxxxxxxx" />
    <Button variant="primary" size="lg" block :loading="loading" type="submit">
      Daftar
    </Button>
  </form>
</template>

<style scoped>
.auth-form {
  display: flex;
  flex-direction: column;
}
</style>

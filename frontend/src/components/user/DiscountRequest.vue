<script setup lang="ts">
import { ref } from 'vue'
import FormInput from '../common/FormInput.vue'
import FormTextarea from '../common/FormTextarea.vue'
import Button from '../common/Button.vue'
import type { Payment } from '../../types'

defineProps<{
  payment: Payment
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [data: { discount_request: string; discount_reason?: string }]
}>()

const discountRequest = ref('')
const discountReason = ref('')

function handleSubmit() {
  emit('submit', { discount_request: discountRequest.value, discount_reason: discountReason.value || undefined })
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <FormInput v-model="discountRequest" label="Jumlah Diskon (Rp)" type="number" required />
    <FormTextarea v-model="discountReason" label="Alasan Pengajuan Diskon" />
    <div class="form-actions">
      <Button type="submit" :disabled="loading">
        {{ loading ? 'Mengirim...' : 'Ajukan Diskon' }}
      </Button>
    </div>
  </form>
</template>

<style scoped>
.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

</style>

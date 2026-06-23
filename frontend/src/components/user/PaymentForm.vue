<script setup lang="ts">
import { ref } from 'vue'
import FormInput from '../common/FormInput.vue'
import FileUpload from '../common/FileUpload.vue'
import Button from '../common/Button.vue'

defineProps<{
  beritaAcaraId: number
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [data: FormData]
}>()

const amount = ref('')
const paymentProof = ref<File | null>(null)

function handleSubmit() {
  const fd = new FormData()
  fd.append('berita_acara_id', String(beritaAcaraId))
  fd.append('amount', amount.value)
  if (paymentProof.value) {
    fd.append('payment_proof', paymentProof.value)
  }
  emit('submit', fd)
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <FormInput v-model="amount" label="Amount" type="number" required />
    <FileUpload v-model="paymentProof" label="Payment Proof" accept="image/*,.pdf" />
    <div class="form-actions">
      <Button type="submit" :disabled="loading">
        {{ loading ? 'Submitting...' : 'Submit Payment' }}
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

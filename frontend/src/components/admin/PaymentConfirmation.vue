<script setup lang="ts">
import { ref } from 'vue'
import FormInput from '../common/FormInput.vue'
import FormSelect from '../common/FormSelect.vue'
import Button from '../common/Button.vue'
import Badge from '../common/Badge.vue'
import type { Payment } from '../../types'

defineProps<{
  payment: Payment
}>()

const emit = defineEmits<{
  confirm: [data: { status: string; discount_approved?: string }]
}>()

const status = ref('')
const discountApproved = ref('')
const loading = ref(false)

const statusOptions = [
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
]

function handleSubmit() {
  loading.value = true
  emit('confirm', { status: status.value, discount_approved: discountApproved.value || undefined })
  loading.value = false
}
</script>

<template>
  <div class="confirmation">
    <div class="confirmation__info">
      <p><strong>Jumlah:</strong> Rp {{ Number(payment.amount).toLocaleString('id-ID') }}</p>
      <p v-if="payment.discount_request"><strong>Diskon Diminta:</strong> Rp {{ Number(payment.discount_request).toLocaleString('id-ID') }}</p>
      <p v-if="payment.discount_reason"><strong>Alasan Diskon:</strong> {{ payment.discount_reason }}</p>
      <p><strong>Status:</strong> <Badge :type="payment.status === 'approved' ? 'success' : payment.status === 'rejected' ? 'danger' : 'warning'" :text="payment.status === 'approved' ? 'Lunas' : payment.status === 'rejected' ? 'Ditolak' : 'Diajukan'" /></p>
    </div>
    <form @submit.prevent="handleSubmit">
      <FormInput v-model="discountApproved" label="Diskon Disetujui (opsional)" type="number" />
      <FormSelect v-model="status" label="Konfirmasi Status" :options="statusOptions" required />
      <div class="confirmation__actions">
        <Button type="submit" :disabled="loading || !status">
          {{ loading ? 'Menyimpan...' : 'Konfirmasi Pembayaran' }}
        </Button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.confirmation__info {
  margin-bottom: 16px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
}
.confirmation__info p {
  margin: 4px 0;
  font-size: 14px;
}
.confirmation__actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

</style>

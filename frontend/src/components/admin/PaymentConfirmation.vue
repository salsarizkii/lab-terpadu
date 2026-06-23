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
      <p><strong>Amount:</strong> Rp {{ payment.amount }}</p>
      <p v-if="payment.discount_request"><strong>Discount Requested:</strong> Rp {{ payment.discount_request }}</p>
      <p><strong>Status:</strong> <Badge :type="payment.status === 'approved' ? 'success' : payment.status === 'rejected' ? 'danger' : 'warning'" :text="payment.status" /></p>
    </div>
    <form @submit.prevent="handleSubmit">
      <FormInput v-model="discountApproved" label="Discount Approved (optional)" type="number" />
      <FormSelect v-model="status" label="Confirm Status" :options="statusOptions" required />
      <div class="confirmation__actions">
        <Button type="submit" :disabled="loading || !status">
          {{ loading ? 'Confirming...' : 'Confirm Payment' }}
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

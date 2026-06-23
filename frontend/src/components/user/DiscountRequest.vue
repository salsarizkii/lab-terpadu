<script setup lang="ts">
import { ref } from 'vue'
import FormInput from '../common/FormInput.vue'
import Button from '../common/Button.vue'
import type { Payment } from '../../types'

defineProps<{
  payment: Payment
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [data: { discount_request: string }]
}>()

const discountRequest = ref('')

function handleSubmit() {
  emit('submit', { discount_request: discountRequest.value })
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <FormInput v-model="discountRequest" label="Discount Request Amount" type="number" required />
    <div class="form-actions">
      <Button type="submit" :disabled="loading">
        {{ loading ? 'Requesting...' : 'Request Discount' }}
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

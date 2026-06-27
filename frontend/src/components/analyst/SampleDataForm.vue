<script setup lang="ts">
import { ref, onMounted } from 'vue'
import FormTextarea from '../common/FormTextarea.vue'
import FormSelect from '../common/FormSelect.vue'
import Button from '../common/Button.vue'
import type { Sample } from '../../types'

const props = defineProps<{
  sample: Sample
  beritaAcaraId: number
}>()

const emit = defineEmits<{
  submit: [data: { test_result?: string; status?: string }]
}>()

const testResult = ref('')
const status = ref('')
const loading = ref(false)

const statusOptions = [
  { value: 'processing', label: 'Processing' },
  { value: 'ready', label: 'Ready' },
]

onMounted(() => {
  testResult.value = props.sample.test_result || ''
  status.value = props.sample.status === 'pending' ? 'processing' : props.sample.status
})

function handleSubmit() {
  loading.value = true
  emit('submit', {
    test_result: testResult.value || undefined,
    status: status.value || undefined,
  })
  loading.value = false
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <div class="sample-info">
      <p><strong>Sample:</strong> {{ sample.name }}</p>
      <p><strong>Quantity:</strong> {{ sample.quantity || '-' }} {{ sample.unit || '' }}</p>
    </div>
    <FormTextarea v-model="testResult" label="Test Result" :rows="4" />
    <FormSelect v-model="status" label="Status" :options="statusOptions" required />
    <div class="form-actions">
      <Button type="submit" :disabled="loading">
        {{ loading ? 'Saving...' : 'Update Sample' }}
      </Button>
    </div>
  </form>
</template>

<style scoped>
.sample-info {
  margin-bottom: 16px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 8px;
}
.sample-info p {
  margin: 4px 0;
  font-size: 14px;
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

</style>

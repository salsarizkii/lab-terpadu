<script setup lang="ts">
import { ref, computed } from 'vue'
import FormSelect from '../common/FormSelect.vue'
import Button from '../common/Button.vue'
import type { User } from '../../types'

const props = defineProps<{
  beritaAcaraId: number
  analysts: User[]
}>()

const emit = defineEmits<{
  assign: [analystId: number]
}>()

const selectedAnalyst = ref('')
const loading = ref(false)

const analystOptions = computed(() =>
  props.analysts.map((a) => ({ value: String(a.id), label: `${a.name} (${a.email})` }))
)

function handleAssign() {
  if (!selectedAnalyst.value) return
  loading.value = true
  emit('assign', Number(selectedAnalyst.value))
  loading.value = false
}
</script>

<template>
  <div class="assignment">
    <FormSelect v-model="selectedAnalyst" label="Select Analyst" :options="analystOptions" required />
    <div class="assignment__actions">
      <Button :disabled="loading || !selectedAnalyst" @click="handleAssign">
        {{ loading ? 'Assigning...' : 'Assign Analyst' }}
      </Button>
    </div>
  </div>
</template>

<style scoped>
.assignment__actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

</style>

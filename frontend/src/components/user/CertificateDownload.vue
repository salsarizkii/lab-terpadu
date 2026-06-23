<script setup lang="ts">
import Button from '../common/Button.vue'

defineProps<{
  certificateId: number
}>()

const emit = defineEmits<{
  download: [id: number]
}>()
</script>

<template>
  <Button @click="emit('download', certificateId)">
    Download Certificate
  </Button>
</template>

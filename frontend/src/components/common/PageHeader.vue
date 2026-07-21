<script setup lang="ts">
import { useRouter } from 'vue-router'
import Button from './Button.vue'
import { ArrowLeft } from '@lucide/vue'

const props = withDefaults(defineProps<{
  title: string
  subtitle?: string
  showBack?: boolean
  backTo?: string
  backLabel?: string
}>(), {
  subtitle: '',
  showBack: false,
  backTo: '',
  backLabel: 'Kembali',
})

const emit = defineEmits<{
  back: []
}>()

const router = useRouter()

function handleBack() {
  emit('back')
  if (props.backTo) {
    router.push(props.backTo)
  } else {
    router.back()
  }
}
</script>

<template>
  <div class="page-header-component">
    <div class="header-left">
      <Button
        v-if="showBack"
        variant="outline"
        size="sm"
        class="btn-back"
        @click="handleBack"
      >
        <ArrowLeft :size="16" style="margin-right: 6px;" />
        <span>{{ backLabel }}</span>
      </Button>

      <div class="title-group">
        <div class="title-row">
          <slot name="icon" />
          <h1 class="page-title">{{ title }}</h1>
        </div>
        <p v-if="subtitle" class="page-subtitle">{{ subtitle }}</p>
      </div>
    </div>

    <div v-if="$slots.actions || $slots.default" class="header-actions">
      <slot name="actions">
        <slot />
      </slot>
    </div>
  </div>
</template>

<style scoped>
.page-header-component {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.btn-back {
  border-radius: var(--radius-md, 10px);
  padding: 8px 14px;
}

.title-group {
  display: flex;
  flex-direction: column;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.page-title {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
  margin: 0;
  line-height: 1.25;
}

.page-subtitle {
  font-size: 13px;
  color: #64748b;
  margin-top: 3px;
  margin-bottom: 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}
</style>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'

const props = withDefaults(defineProps<{
 teleport?: boolean
 placement?: 'bottom-start' | 'bottom-end' | 'bottom-center'
 width?: string
}>(), {
 teleport: false,
 placement: 'bottom-end',
 width: 'auto',
})

const emit = defineEmits<{
 open: []
 close: []
}>()

const isOpen = ref(false)
const triggerRef = ref<HTMLElement | null>(null)
const menuRef = ref<HTMLElement | null>(null)
const menuStyle = ref<Record<string, string>>({})

function toggle() {
 isOpen.value ? close() : open()
}

function open() {
  isOpen.value = true
  emit('open')
  if (props.teleport) {
    // Render dulu tersembunyi di (0,0) agar tidak mem-flash di pojok kiri atas,
    // lalu ukur posisi sebenarnya dan tampilkan.
    menuStyle.value = {
      position: 'fixed',
      top: '0px',
      left: '0px',
      width: props.width,
      visibility: 'hidden',
    }
    nextTick(() => {
      updatePosition()
      menuStyle.value = { ...menuStyle.value, visibility: 'visible' }
    })
  }
}

function close() {
 if (!isOpen.value) return
 isOpen.value = false
 emit('close')
}

function onClickOutside(e: MouseEvent) {
 const target = e.target as HTMLElement
 if (triggerRef.value?.contains(target)) return
 if (menuRef.value?.contains(target)) return
 close()
}

function updatePosition() {
 if (!triggerRef.value || !menuRef.value) return
 const triggerRect = triggerRef.value.getBoundingClientRect()
 const menuRect = menuRef.value.getBoundingClientRect()

 let top = triggerRect.bottom + 4
 let left: number

 if (props.placement === 'bottom-end') {
   left = triggerRect.right - menuRect.width
 } else if (props.placement === 'bottom-start') {
   left = triggerRect.left
 } else {
   left = triggerRect.left + (triggerRect.width - menuRect.width) / 2
 }

 // Keep within viewport
 if (left < 8) left = 8
 if (left + menuRect.width > window.innerWidth - 8) left = window.innerWidth - menuRect.width - 8
 if (top + menuRect.height > window.innerHeight - 8) top = triggerRect.top - menuRect.height - 4

 menuStyle.value = {
   position: 'fixed',
   top: `${top}px`,
   left: `${left}px`,
   width: props.width,
 }
}

function onWindowResize() {
  if (isOpen.value && props.teleport) updatePosition()
}

onMounted(() => {
  document.addEventListener('mousedown', onClickOutside)
  window.addEventListener('resize', onWindowResize)
})
onUnmounted(() => {
  document.removeEventListener('mousedown', onClickOutside)
  window.removeEventListener('resize', onWindowResize)
})

watch(isOpen, (v) => {
 if (v) document.body.style.overflow = 'hidden'
 else document.body.style.overflow = ''
})

defineExpose({ open, close, toggle })
</script>

<template>
 <div class="dropdown" ref="triggerRef">
   <div class="dropdown__trigger" @click="toggle">
     <slot name="trigger" :is-open="isOpen" />
   </div>

   <Teleport v-if="teleport" to="body">
     <Transition name="dropdown">
       <div
         v-if="isOpen"
         ref="menuRef"
         class="dropdown__menu"
         :style="menuStyle"
         @click.stop
       >
         <slot :close="close" />
       </div>
     </Transition>
     <div v-if="isOpen" class="dropdown__backdrop" @click="close" />
   </Teleport>

   <Transition v-else name="dropdown">
     <div
       v-if="isOpen"
       ref="menuRef"
       class="dropdown__menu"
       :class="`dropdown__menu--${placement}`"
       :style="{ width }"
       @click.stop
     >
       <slot :close="close" />
     </div>
   </Transition>
 </div>
</template>

<style scoped>
.dropdown {
 position: relative;
 display: inline-flex;
 }

.dropdown__trigger {
 display: inline-flex;
 }

.dropdown__menu {
 background: var(--color-surface);
 border: 1.5px solid var(--color-border);
 border-radius: 10px;
 box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
 min-width: 160px;
 padding: 4px;
 z-index: 9999;
 }

.dropdown__menu--bottom-start {
 position: absolute;
 top: calc(100% + 4px);
 left: 0;
 }

.dropdown__menu--bottom-end {
 position: absolute;
 top: calc(100% + 4px);
 right: 0;
 }

.dropdown__menu--bottom-center {
 position: absolute;
 top: calc(100% + 4px);
 left: 50%;
 transform: translateX(-50%);
 }

.dropdown__backdrop {
 position: fixed;
 inset: 0;
 z-index: 9998;
 }

.dropdown-enter-active,
.dropdown-leave-active {
 transition: all 0.15s ease;
 }

.dropdown-enter-from,
.dropdown-leave-to {
 opacity: 0;
 transform: translateY(-4px);
 }
</style>

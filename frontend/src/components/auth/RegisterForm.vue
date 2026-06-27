<script setup lang="ts">
import { ref, onMounted } from 'vue'
import FormInput from '../common/FormInput.vue'
import Button from '../common/Button.vue'
import { CheckCircle2, Circle } from '@lucide/vue'
import { roleApi } from '../../api/role'
import type { Role } from '../../types'

defineProps<{
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [data: {
    name: string
    email: string
    password: string
    password_confirmation: string
    phone?: string
    role: string
  }]
}>()

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const phone = ref('')
const selectedRole = ref('')
const roles = ref<Role[]>([])
const loadingRoles = ref(true)

onMounted(async () => {
  try {
    roles.value = await roleApi.getPublicRoles()
    if (roles.value.length === 0) {
      // No roles seeded yet - default to 'user'
      selectedRole.value = 'user'
    } else {
      const userRole = roles.value.find(r => r.name === 'user')
      if (userRole) selectedRole.value = userRole.name
    }
  } catch {
    selectedRole.value = 'user'
  } finally {
    loadingRoles.value = false
  }
})

function handleSubmit() {
  emit('submit', {
    name: name.value,
    email: email.value,
    password: password.value,
    password_confirmation: passwordConfirmation.value,
    phone: phone.value || undefined,
    role: selectedRole.value,
  })
}
</script>

<template>
  <form class="auth-form" @submit.prevent="handleSubmit">
    <FormInput v-model="name" label="Nama Lengkap" placeholder="Nama lengkap" required />
    <FormInput v-model="email" label="Email" type="email" placeholder="contoh@email.com" required />
    <FormInput v-model="password" label="Password" type="password" placeholder="Min. 8 karakter" required />
    <FormInput v-model="passwordConfirmation" label="Konfirmasi Password" type="password" placeholder="Ulangi password" required />
    <FormInput v-model="phone" label="No. Telepon (opsional)" type="tel" placeholder="08xxxxxxxxxx" />

    <div class="role-section">
      <label class="role-label">
        Apa role Anda? <span class="role-required">*</span>
      </label>
      <div v-if="loadingRoles" class="role-loading">Memuat daftar role...</div>
      <div v-else-if="roles.length === 0" class="role-loading">Anda akan terdaftar sebagai <strong>user</strong> (dapat diubah oleh superadmin nanti).</div>
      <div v-else class="role-list">
        <label
          v-for="role in roles"
          :key="role.name"
          class="role-card"
          :class="{ 'role-card--selected': selectedRole === role.name }"
        >
          <input
            type="radio"
            :value="role.name"
            v-model="selectedRole"
            name="role"
            class="role-radio"
            required
          />
          <div class="role-card__content">
            <div class="role-card__header">
              <span class="role-card__name">{{ role.name }}</span>
              <span v-if="role.name !== 'user'" class="role-card__badge">Perlu Verifikasi</span>
              <span v-else class="role-card__badge role-card__badge--auto">Otomatis Terverifikasi</span>
            </div>
            <p v-if="role.description" class="role-card__desc">{{ role.description }}</p>
          </div>
          <div class="role-card__check">
            <CheckCircle2 v-if="selectedRole === role.name" :size="20" />
            <Circle v-else :size="20" />
          </div>
        </label>
      </div>
    </div>

    <Button variant="primary" size="lg" block :loading="loading" type="submit">
      Daftar
    </Button>
  </form>
</template>

<style scoped>
.auth-form {
  display: flex;
  flex-direction: column;
}

.role-section {
  margin-bottom: 20px;
}

.role-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  margin-bottom: 10px;
  letter-spacing: 0.3px;
}

.role-required {
  color: #ef4444;
  margin-left: 2px;
}

.role-loading {
  text-align: center;
  padding: 20px;
  color: #94a3b8;
  font-size: 13px;
}

.role-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.role-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: #fafbfc;
}

.role-card:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.role-card--selected {
  border-color: #2d6a9f;
  background: #f0f7ff;
  box-shadow: 0 0 0 3px rgba(45, 106, 159, 0.1);
}

.role-radio {
  display: none;
}

.role-card__content {
  flex: 1;
  min-width: 0;
}

.role-card__header {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.role-card__name {
  font-weight: 600;
  font-size: 14px;
  color: #1e293b;
  text-transform: capitalize;
}

.role-card__badge {
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 20px;
  background: #fef3c7;
  color: #92400e;
  font-weight: 500;
  letter-spacing: 0.3px;
}

.role-card__badge--auto {
  background: #d1fae5;
  color: #065f46;
}

.role-card__desc {
  margin: 4px 0 0;
  font-size: 12px;
  color: #64748b;
  line-height: 1.4;
}

.role-card__check {
  flex-shrink: 0;
  color: #94a3b8;
  display: flex;
  align-items: center;
}

.role-card--selected .role-card__check {
  color: #2d6a9f;
}
</style>

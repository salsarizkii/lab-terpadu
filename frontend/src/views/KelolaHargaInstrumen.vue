<template>
  <div class="kelola-harga">
    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">Kelola Harga Instrumen</h1>
        <p class="page-subtitle">Daftar harga instrumen analisis laboratorium</p>
      </div>
      <div class="page-header-right">
        <button class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
          Tambah Instrumen
        </button>
      </div>
    </div>

    <div class="content-card">
      <div class="card-header">
        <div class="search-box">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
          <input type="text" v-model="search" placeholder="Cari instrumen..." />
        </div>
        <span class="item-count">{{ filteredItems.length }} instrumen</span>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Memuat data...</p>
      </div>

      <div v-else-if="filteredItems.length === 0" class="empty-state">
        <p>Belum ada data instrumen</p>
      </div>

      <table v-else class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th class="col-name">Nama Instrumen</th>
            <th class="col-unit">Satuan</th>
            <th class="col-price">Harga</th>
            <th class="col-price">Harga UNS</th>
            <th class="col-note">Catatan</th>
            <th class="col-actions">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in filteredItems" :key="item.id" :class="{ 'editing': editingId === item.id }">
            <td class="col-no">{{ index + 1 }}</td>
            <td class="col-name">
              <span v-if="editingId !== item.id">{{ item.name }}</span>
              <input v-else v-model="editForm.name" class="edit-input" placeholder="Nama instrumen" />
            </td>
            <td class="col-unit">
              <span v-if="editingId !== item.id">{{ item.unit || '-' }}</span>
              <input v-else v-model="editForm.unit" class="edit-input edit-input-sm" placeholder="Satuan" />
            </td>
            <td class="col-price">
              <span v-if="editingId !== item.id" class="price">Rp{{ formatPrice(item.price_regular) }}</span>
              <input v-else v-model.number="editForm.price_regular" type="number" class="edit-input edit-input-price" placeholder="Harga" />
            </td>
            <td class="col-price">
              <span v-if="editingId !== item.id" class="price">{{ item.price_uns ? `Rp${formatPrice(item.price_uns)}` : '-' }}</span>
              <input v-else v-model.number="editForm.price_uns" type="number" class="edit-input edit-input-price" placeholder="Harga UNS" />
            </td>
            <td class="col-note">
              <span v-if="editingId !== item.id">{{ item.note || '-' }}</span>
              <input v-else v-model="editForm.note" class="edit-input" placeholder="Catatan" />
            </td>
            <td class="col-actions">
              <div v-if="editingId !== item.id" class="action-buttons">
                <button class="btn-icon btn-icon-edit" @click="startEdit(item)" title="Edit">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                </button>
                <button class="btn-icon btn-icon-delete" @click="deleteItem(item)" title="Hapus">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                </button>
              </div>
              <div v-else class="action-buttons">
                <button class="btn-icon btn-icon-save" @click="saveEdit(item.id)" title="Simpan">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </button>
                <button class="btn-icon btn-icon-cancel" @click="cancelEdit" title="Batal">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div v-if="showAddModal" class="modal-overlay" @click.self="showAddModal = false">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Tambah Instrumen Baru</h2>
            <button class="btn-close" @click="showAddModal = false">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>Nama Instrumen <span class="required">*</span></label>
              <input v-model="addForm.name" class="form-input" placeholder="Masukkan nama instrumen" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Satuan</label>
                <input v-model="addForm.unit" class="form-input" placeholder="Contoh: Per sampel" />
              </div>
              <div class="form-group">
                <label>Catatan</label>
                <input v-model="addForm.note" class="form-input" placeholder="Contoh: Dalam UNS" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Harga Reguler <span class="required">*</span></label>
                <input v-model.number="addForm.price_regular" type="number" class="form-input" placeholder="0" />
              </div>
              <div class="form-group">
                <label>Harga UNS</label>
                <input v-model.number="addForm.price_uns" type="number" class="form-input" placeholder="0 (opsional)" />
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-secondary" @click="showAddModal = false">Batal</button>
            <button class="btn-primary" @click="createItem" :disabled="!addForm.name || !addForm.price_regular">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
              Tambah
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { instrumentPriceApi, type InstrumentPrice } from '@/api/instrumentPrice'
import { toast } from 'vue-sonner'

const loading = ref(true)
const items = ref<InstrumentPrice[]>([])
const search = ref('')
const editingId = ref<number | null>(null)
const editForm = ref<Partial<InstrumentPrice>>({})
const showAddModal = ref(false)
const addForm = ref<Omit<InstrumentPrice, 'id'>>({
  name: '',
  unit: '',
  price_regular: 0,
  price_uns: null,
  note: null,
  sort_order: 0,
})

const filteredItems = computed(() => {
  if (!search.value) return items.value
  const q = search.value.toLowerCase()
  return items.value.filter(i =>
    i.name.toLowerCase().includes(q) ||
    (i.unit && i.unit.toLowerCase().includes(q)) ||
    (i.note && i.note.toLowerCase().includes(q))
  )
})

function formatPrice(val: number) {
  return new Intl.NumberFormat('id-ID').format(val)
}

async function loadItems() {
  loading.value = true
  try {
    const res = await instrumentPriceApi.getAll()
    items.value = res.data
  } catch (e: any) {
    toast.error('Gagal memuat data instrumen')
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  addForm.value = { name: '', unit: '', price_regular: 0, price_uns: null, note: null, sort_order: items.value.length + 1 }
  showAddModal.value = true
}

async function createItem() {
  try {
    await instrumentPriceApi.create(addForm.value)
    toast.success('Instrumen berhasil ditambahkan')
    showAddModal.value = false
    await loadItems()
  } catch (e: any) {
    toast.error('Gagal menambahkan instrumen')
  }
}

function startEdit(item: InstrumentPrice) {
  editingId.value = item.id
  editForm.value = { ...item }
}

function cancelEdit() {
  editingId.value = null
  editForm.value = {}
}

async function saveEdit(id: number) {
  try {
    await instrumentPriceApi.update(id, editForm.value)
    toast.success('Instrumen berhasil diperbarui')
    editingId.value = null
    editForm.value = {}
    await loadItems()
  } catch (e: any) {
    toast.error('Gagal memperbarui instrumen')
  }
}

async function deleteItem(item: InstrumentPrice) {
  if (!confirm(`Hapus "${item.name}"?`)) return
  try {
    await instrumentPriceApi.delete(item.id)
    toast.success('Instrumen berhasil dihapus')
    await loadItems()
  } catch (e: any) {
    toast.error('Gagal menghapus instrumen')
  }
}

onMounted(loadItems)
</script>

<style scoped>
.kelola-harga {
  padding: 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
}

.page-header-left {
  flex: 1;
}

.page-header-right {
  flex-shrink: 0;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0;
}

.page-subtitle {
  font-size: 14px;
  color: var(--color-text-secondary);
  margin: 4px 0 0;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--radius-md);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-primary:hover {
  background: var(--color-primary-hover);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.content-card {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  border-bottom: 1px solid var(--color-border);
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  flex: 1;
  max-width: 360px;
  color: var(--color-text-secondary);
}

.search-box input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 14px;
  color: var(--color-text-primary);
  width: 100%;
}

.item-count {
  font-size: 13px;
  color: var(--color-text-secondary);
  white-space: nowrap;
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: var(--color-text-secondary);
  gap: 12px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  text-align: left;
  padding: 12px 16px;
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border);
  white-space: nowrap;
}

.data-table td {
  padding: 12px 16px;
  font-size: 14px;
  color: var(--color-text-primary);
  border-bottom: 1px solid var(--color-border);
  vertical-align: middle;
}

.data-table tr:hover {
  background: var(--color-bg-secondary);
}

.data-table tr.editing {
  background: #eff6ff;
}

.col-no { width: 50px; }
.col-name { min-width: 280px; }
.col-unit { width: 140px; }
.col-price { width: 150px; }
.col-note { min-width: 120px; }
.col-actions { width: 100px; }

.price {
  font-weight: 600;
  color: var(--color-primary);
}

.edit-input {
  width: 100%;
  padding: 6px 10px;
  border: 1px solid var(--color-primary);
  border-radius: var(--radius-sm);
  font-size: 13px;
  background: white;
  outline: none;
}

.edit-input:focus {
  box-shadow: 0 0 0 2px rgba(var(--color-primary-rgb), 0.15);
}

.edit-input-sm { max-width: 120px; }
.edit-input-price { max-width: 140px; }

.action-buttons {
  display: flex;
  gap: 4px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.15s ease;
  background: transparent;
  color: var(--color-text-secondary);
}

.btn-icon:hover {
  background: var(--color-bg-secondary);
}

.btn-icon-edit { color: var(--color-primary); }
.btn-icon-edit:hover { background: #eff6ff; }
.btn-icon-delete { color: #ef4444; }
.btn-icon-delete:hover { background: #fef2f2; }
.btn-icon-save { color: #10b981; }
.btn-icon-save:hover { background: #ecfdf5; }
.btn-icon-cancel { color: var(--color-text-secondary); }
.btn-icon-cancel:hover { background: var(--color-bg-secondary); }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  width: 100%;
  max-width: 560px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid var(--color-border);
}

.modal-header h2 {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0;
}

.btn-close {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  font-size: 22px;
  color: var(--color-text-secondary);
  cursor: pointer;
  border-radius: var(--radius-sm);
}

.btn-close:hover {
  background: var(--color-bg-secondary);
}

.modal-body {
  padding: 24px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid var(--color-border);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.required { color: #ef4444; }

.form-input {
  padding: 10px 14px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
  color: var(--color-text-primary);
  background: var(--color-bg-primary);
  outline: none;
  transition: border-color 0.15s ease;
}

.form-input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(var(--color-primary-rgb), 0.15);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.btn-secondary {
  padding: 10px 18px;
  background: var(--color-bg-secondary);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-secondary:hover {
  background: var(--color-bg-tertiary);
}
</style>

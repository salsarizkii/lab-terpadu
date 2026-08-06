<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import Card from '../components/common/Card.vue'
import Badge from '../components/common/Badge.vue'
import SkeletonTable from '../components/common/SkeletonTable.vue'
import Button from '../components/common/Button.vue'
import Modal from '../components/common/Modal.vue'
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
  } catch {
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
  } catch {
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
  } catch {
    toast.error('Gagal memperbarui instrumen')
  }
}

async function deleteItem(item: InstrumentPrice) {
  if (!confirm(`Hapus "${item.name}"?`)) return
  try {
    await instrumentPriceApi.delete(item.id)
    toast.success('Instrumen berhasil dihapus')
    await loadItems()
  } catch {
    toast.error('Gagal menghapus instrumen')
  }
}

onMounted(loadItems)
</script>

<template>
    <div>
      <div class="page-header">
        <h1>Kelola Harga Instrumen</h1>
        <Button @click="openAddModal">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
          Tambah Instrumen
        </Button>
      </div>

      <Card>
        <div class="toolbar">
          <div class="search-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" v-model="search" placeholder="Cari instrumen..." />
          </div>
          <span class="filter-count">{{ filteredItems.length }} data</span>
        </div>

        <SkeletonTable v-if="loading" :cols="7" :rows="8" />

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Nama Instrumen</th>
                <th>Satuan</th>
                <th>Harga</th>
                <th>Harga UNS</th>
                <th>Catatan</th>
                <th class="th-aksi">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredItems" :key="item.id" :class="{ 'row-editing': editingId === item.id }">
                <td class="td-name">
                  <span v-if="editingId !== item.id">{{ item.name }}</span>
                  <input v-else v-model="editForm.name" class="inline-input" placeholder="Nama instrumen" />
                </td>
                <td>
                  <span v-if="editingId !== item.id">{{ item.unit || '-' }}</span>
                  <input v-else v-model="editForm.unit" class="inline-input inline-input--sm" placeholder="Satuan" />
                </td>
                <td class="td-price">
                  <span v-if="editingId !== item.id">Rp{{ formatPrice(item.price_regular) }}</span>
                  <input v-else v-model.number="editForm.price_regular" type="number" class="inline-input inline-input--price" placeholder="Harga" />
                </td>
                <td class="td-price">
                  <span v-if="editingId !== item.id">{{ item.price_uns ? `Rp${formatPrice(item.price_uns)}` : '-' }}</span>
                  <input v-else v-model.number="editForm.price_uns" type="number" class="inline-input inline-input--price" placeholder="Harga UNS" />
                </td>
                <td>
                  <span v-if="editingId !== item.id">{{ item.note || '-' }}</span>
                  <input v-else v-model="editForm.note" class="inline-input" placeholder="Catatan" />
                </td>
                <td class="td-aksi">
                  <div v-if="editingId !== item.id" class="action-btns">
                    <button class="eye-btn" @click="startEdit(item)" title="Edit">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    </button>
                    <button class="eye-btn eye-btn--danger" @click="deleteItem(item)" title="Hapus">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                  </div>
                  <div v-else class="action-btns">
                    <button class="eye-btn eye-btn--success" @click="saveEdit(item.id)" title="Simpan">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                    <button class="eye-btn" @click="cancelEdit" title="Batal">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredItems.length === 0">
                <td colspan="6" class="empty-cell">Tidak ada data instrumen</td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <Modal :show="showAddModal" title="Tambah Instrumen Baru" @close="showAddModal = false">
        <div class="form-group">
          <label>Nama Instrumen <span class="required">*</span></label>
          <input v-model="addForm.name" class="form-input" placeholder="Masukkan nama instrumen" />
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Satuan</label>
            <input v-model="addForm.unit" class="form-input" placeholder="Contoh: Per hasil pengujian" />
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
        <template #footer>
          <Button variant="outline" @click="showAddModal = false">Batal</Button>
          <Button :disabled="!addForm.name || !addForm.price_regular" @click="createItem">Tambah</Button>
        </template>
      </Modal>
    </div>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.page-header h1 {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.toolbar {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  flex: 1;
  max-width: 360px;
  color: #94a3b8;
}
.search-box input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 14px;
  color: #1e293b;
  width: 100%;
}

.filter-count {
  font-size: 13px;
  color: #64748b;
  white-space: nowrap;
}

.table-wrap { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: 14px; }
.table th {
  text-align: left;
  padding: 12px;
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}
.table td {
  padding: 12px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}
.table tr:hover { background: #f8fafc; }
.table tr.row-editing { background: #eff6ff; }

.th-aksi { width: 100px; text-align: center; }
.td-name { min-width: 280px; font-weight: 500; }
.td-price { white-space: nowrap; font-weight: 600; color: #1e40af; }
.td-aksi { text-align: center; }

.empty-cell { text-align: center; color: #94a3b8; padding: 40px; }

.inline-input {
  width: 100%;
  padding: 6px 10px;
  border: 1px solid #3b82f6;
  border-radius: 6px;
  font-size: 13px;
  background: white;
  outline: none;
}
.inline-input:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}
.inline-input--sm { max-width: 120px; }
.inline-input--price { max-width: 140px; }

.action-btns {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.eye-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  background: transparent;
  color: #64748b;
  transition: all 0.15s;
}
.eye-btn:hover { background: #f1f5f9; color: #1e293b; }
.eye-btn--success { color: #16a34a; }
.eye-btn--success:hover { background: #f0fdf4; }
.eye-btn--danger { color: #dc2626; }
.eye-btn--danger:hover { background: #fef2f2; }

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
}
.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}
.required { color: #ef4444; }
.form-input {
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  color: #1e293b;
  background: white;
  outline: none;
  transition: border-color 0.15s;
}
.form-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
</style>

export interface Permission {
  id: number
  name: string
  display_name: string
  group: string
  description?: string
}

export interface Role {
  id: number
  name: string
  description: string | null
  is_system: boolean
  requires_verification: boolean
  users_count?: number
  permissions?: Permission[]
  pivot?: { permission_id: number }
  created_at?: string
  updated_at?: string
}

export interface User {
  id: number
  name: string
  email: string
  role: string
  phone?: string
  is_verified: boolean
  permissions?: string[]
  created_at: string
}


export interface BeritaAcara {
  id: number
  user_id: number
  analyst_id?: number
  status: 'draft' | 'pending' | 'revision' | 'approved' | 'rejected'
  nama_pemohon?: string
  nim?: string
  prodi_fakultas_instansi?: string
  dosen_pembimbing?: string
  telp_hp?: string
  email?: string
  alamat?: string
  judul_penelitian?: string
  akses_lab?: string
  bahan_contoh_uji?: string
  kemasan_packing?: string
  permintaan_analisa?: string
  jumlah_sampel?: string
  kode_nama_sampel?: string
  keterangan_sampel?: string
  keterangan_tambahan?: string
  pengukuran_per_sampel?: string
  sisa_sampel?: string
  upload_akses?: string
  masa_berlaku_akses?: string
  status_pelanggan?: string
  status_berkas?: string
  biaya?: string
  diskon_diizinkan?: boolean
  formula_id?: number | null
  formula?: { id: number; name: string; expression: string; unit: string }
  custom_items?: Array<{ name: string; amount: number }>
  keterangan?: string
  form_data?: Record<string, any>
  form_template_id?: number
  rekap_analis_data?: Record<string, any>
  rekap_analis_template_id?: number
  user?: User
  analyst?: User
  analysts?: User[]
  revisions?: BeritaAcaraRevision[]
  payment?: Payment
  samples?: Sample[]
  certificate?: Certificate
  pengujians?: any[]
  created_at: string
  updated_at: string
}

export interface BeritaAcaraRevision {
  id: number
  berita_acara_id: number
  note: string
  previous_form_data?: Record<string, any>
  created_by: number
  creator?: User
  created_at: string
}

export interface Payment {
  id: number
  berita_acara_id: number
  amount: string
  discount_request?: string
  discount_reason?: string
  discount_status?: 'pending' | 'approved' | 'rejected'
  discount_approved?: string
  discount_type?: string
  discount_percent?: number
  discount_nominal?: number
  status: 'pending' | 'approved' | 'rejected'
  payment_proof?: string
  keterangan?: string
  rejection_reason?: string
  berita_acara?: BeritaAcara
  created_at: string
}

export interface Sample {
  id: number
  berita_acara_id: number
  name: string
  description?: string
  quantity?: string
  unit?: string
  test_result?: string
  status: 'pending' | 'processing' | 'ready' | 'taken'
  created_at: string
  berita_acara?: {
    id: number
    analyst_id?: number
    user?: User
    analyst?: User
    rekap_analis_data?: Record<string, any>
    custom_items?: Array<{ name: string; amount: number }>
    biaya?: string
    keterangan?: string
    formula?: { id: number; name: string; expression: string; unit: string }
    payment?: Payment
  }
}

export interface Certificate {
  id: number
  berita_acara_id: number
  file_path?: string
  downloaded_at?: string
}

export interface FormFieldOption {
  label: string
  is_other: boolean
}

export interface FormField {
  id?: number
  label: string
  type: 'text' | 'textarea' | 'number' | 'select' | 'checkbox' | 'radio' | 'date' | 'time' | 'email' | 'phone' | 'file' | 'heading' | 'paragraph' | 'divider'
  options?: FormFieldOption[]
  is_required: boolean
  sort_order?: number
  locked?: boolean
}

export interface FormTemplate {
  id: number
  name: string
  type: 'berita_acara' | 'rekap_analis' | 'perhitungan_biaya' | 'kelola_harga_instrumen'
  description?: string
  is_active: boolean
  is_default: boolean
  created_by: number
  fields_count?: number
  fields?: FormField[]
  creator?: { id: number; name: string }
  created_at: string
  updated_at: string
}

export interface PdfVariable {
  key: string
  label: string
  example: string
}

export interface PdfTemplate {
  id: number
  type: 'berita_acara' | 'rekap_analis' | 'tagihan' | 'sertifikat'
  name: string
  html_content: string
  docx_path?: string | null
  paper_size: 'a4' | 'letter' | 'legal'
  orientation: 'portrait' | 'landscape'
  margin_top: number
  margin_bottom: number
  margin_left: number
  margin_right: number
  is_active: boolean
  created_at?: string
  updated_at?: string
}


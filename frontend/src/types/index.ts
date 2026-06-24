export interface User {
  id: number
  name: string
  email: string
  role: 'user' | 'admin' | 'analyst'
  phone?: string
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
  user?: User
  analyst?: User
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
  discount_approved?: string
  status: 'pending' | 'approved' | 'rejected'
  payment_proof?: string
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
}

export interface Certificate {
  id: number
  berita_acara_id: number
  file_path?: string
  downloaded_at?: string
}

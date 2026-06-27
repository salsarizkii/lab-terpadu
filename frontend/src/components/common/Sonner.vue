<script setup lang="ts">
import { Toaster } from 'vue-sonner'
import 'vue-sonner/style.css'
</script>

<template>
  <!-- 
    Konfigurasi global untuk Toast Notifikasi.
    richColors: menggunakan warna yang lebih vibrant (hijau untuk sukses, merah untuk error)
    expand: jika ada multiple toast, mereka akan melebar alih-alih menumpuk jadi satu
    position: bagian tengah atas layar
  -->
  <Toaster position="top-center" richColors expand />
</template>

<style>
/* Anda bisa menambahkan styling global khusus untuk sonner di sini jika diperlukan */
</style>

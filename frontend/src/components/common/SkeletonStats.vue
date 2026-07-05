<script setup lang="ts">
import Skeleton from './Skeleton.vue'
</script>

<template>
 <div class="sk-stats">
   <div v-for="n in 4" :key="n" class="sk-stats__card">
     <Skeleton width="50%" height="28px" />
     <Skeleton width="70%" height="12px" style="margin-top: 8px" />
   </div>
 </div>
</template>

<style scoped>
.sk-stats {
 display: grid;
 grid-template-columns: repeat(4, 1fr);
 gap: 16px;
 margin-bottom: 24px;
 }
.sk-stats__card {
 padding: 20px;
 background: #fff;
 border: 1px solid #e2e8f0;
 border-radius: 12px;
 }
@media (max-width: 768px) {
 .sk-stats { grid-template-columns: repeat(2, 1fr); }
 }
</style>

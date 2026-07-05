<script setup lang="ts">
import Skeleton from './Skeleton.vue'
</script>

<template>
 <div class="sk-detail">
   <div class="sk-detail__header">
     <Skeleton width="80px" height="32px" rounded="8px" />
     <Skeleton width="160px" height="32px" rounded="8px" />
   </div>
   <div class="sk-detail__grid">
     <div class="sk-detail__main">
       <div class="sk-detail__card">
         <Skeleton width="40%" height="18px" style="margin-bottom: 20px" />
         <div v-for="n in 6" :key="n" class="sk-detail__row">
           <Skeleton width="120px" height="13px" />
           <Skeleton width="60%" height="13px" />
         </div>
       </div>
     </div>
     <div class="sk-detail__sidebar">
       <div class="sk-detail__card">
         <Skeleton width="30%" height="18px" style="margin-bottom: 16px" />
         <Skeleton height="36px" rounded="8px" style="margin-bottom: 8px" />
         <Skeleton height="36px" rounded="8px" style="margin-bottom: 8px" />
         <Skeleton height="36px" rounded="8px" />
       </div>
     </div>
   </div>
 </div>
</template>

<style scoped>
.sk-detail__header {
 display: flex;
 justify-content: space-between;
 align-items: center;
 margin-bottom: 24px;
 }
.sk-detail__grid {
 display: grid;
 grid-template-columns: 1fr 360px;
 gap: 24px;
 align-items: start;
 }
@media (max-width: 900px) {
 .sk-detail__grid { grid-template-columns: 1fr; }
 }
.sk-detail__card {
 padding: 20px;
 background: #fff;
 border: 1px solid #e2e8f0;
 border-radius: 12px;
 }
.sk-detail__row {
 display: flex;
 justify-content: space-between;
 padding: 10px 0;
 border-bottom: 1px solid #f1f5f9;
 }
.sk-detail__row:last-child {
 border-bottom: none;
 }
</style>

<script setup lang="ts">
withDefaults(defineProps<{
  columns: { key: string; label: string }[]
  data: any[]
  loading?: boolean
  emptyText?: string
}>(), {
  loading: false,
  emptyText: 'Tidak ada data',
})
</script>

<template>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th v-for="col in columns" :key="col.key">{{ col.label }}</th>
          <th v-if="$slots['th-after']"><slot name="th-after" /></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row, idx) in data" :key="idx">
          <td v-for="col in columns" :key="col.key">
            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
              {{ row[col.key] }}
            </slot>
          </td>
          <td v-if="$slots['td-after']">
            <slot name="td-after" :row="row" />
          </td>
        </tr>
        <tr v-if="data.length === 0 && !loading">
          <td :colspan="columns.length + ($slots['th-after'] ? 1 : 0)" class="table__empty">
            {{ emptyText }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.table-wrap {
  overflow-x: auto;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}
.table {
  width: 100%;
  border-collapse: collapse;
  font-family: var(--font-primary);
  font-size: 14px;
}
.table th {
  text-align: left;
  padding: 12px 16px;
  background: var(--color-bg);
  color: var(--color-text-secondary);
  font-weight: 500;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--color-border);
}
.table td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--color-border-light);
  color: var(--color-text);
}
.table tr:last-child td {
  border-bottom: none;
}
.table tr:hover td {
  background: var(--color-bg);
}
.table__empty {
  text-align: center;
  color: var(--color-text-muted);
  padding: 40px 16px;
  font-size: 14px;
}
</style>

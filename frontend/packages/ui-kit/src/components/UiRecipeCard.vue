<script setup lang="ts">
const props = defineProps<{
  title: string;
  meta?: string;
  badge?: string;
}>();
</script>

<template>
  <article class="card">
    <h4>{{ props.title }}</h4>
    <p v-if="props.meta" class="meta">{{ props.meta }}</p>
    <span v-if="props.badge" class="badge">{{ props.badge }}</span>
    <div class="actions">
      <slot name="actions" />
    </div>
  </article>
</template>

<style scoped>
.card {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-elevated);
  padding: var(--space-md);
  display: grid;
  gap: var(--space-sm);
}

h4 {
  margin: 0;
  font-size: var(--font-size-body);
}

.meta {
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--font-size-caption);
}

.badge {
  display: inline-flex;
  width: fit-content;
  border-radius: 999px;
  padding: 2px 8px;
  font-size: var(--font-size-caption);
  background: color-mix(in srgb, var(--color-surface) 85%, var(--color-accent));
}

.actions {
  display: flex;
  gap: var(--space-sm);
  flex-wrap: wrap;
}
</style>

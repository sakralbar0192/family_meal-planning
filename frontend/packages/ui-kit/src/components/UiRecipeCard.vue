<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string;
    meta?: string;
    badge?: string;
  }>(),
  { title: '' },
);
</script>

<template>
  <article class="card">
    <div class="card-top">
      <slot name="top">
        <h4>{{ title }}</h4>
        <p v-if="meta" class="meta">{{ meta }}</p>
        <span v-if="badge" class="badge">{{ badge }}</span>
      </slot>
    </div>
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
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.card-top {
  min-width: 0;
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

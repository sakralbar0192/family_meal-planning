<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    title: string;
    open: boolean;
  }>(),
  {
    open: false,
  },
);

const emit = defineEmits<{
  (e: 'close'): void;
}>();
</script>

<template>
  <div v-if="props.open" class="backdrop" role="dialog" aria-modal="true" :aria-label="props.title" @click.self="emit('close')">
    <div class="modal">
      <header class="head">
        <h3>{{ props.title }}</h3>
      </header>
      <div class="body">
        <slot />
      </div>
      <footer class="actions">
        <slot name="actions" />
      </footer>
    </div>
  </div>
</template>

<style scoped>
.backdrop {
  position: fixed;
  inset: 0;
  background: var(--color-overlay);
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-lg);
}

.modal {
  width: 100%;
  max-width: 28rem;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-surface);
  padding: var(--space-lg);
  display: grid;
  gap: var(--space-md);
}

.head h3 {
  margin: 0;
  font-size: var(--font-size-title);
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-sm);
}
</style>

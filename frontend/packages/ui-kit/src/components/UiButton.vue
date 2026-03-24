<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'danger';
    size?: 'md' | 'sm';
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
  }>(),
  {
    variant: 'primary',
    size: 'md',
    disabled: false,
    type: 'button',
  },
);
</script>

<template>
  <button :type="props.type" class="ui-btn" :class="[props.variant, props.size]" :disabled="props.disabled">
    <slot />
  </button>
</template>

<style scoped>
.ui-btn {
  min-height: var(--button-min-height);
  padding: 0 var(--space-md);
  border-radius: var(--radius-md);
  border: none;
  background: var(--color-accent);
  color: var(--color-text-on-accent);
  font-weight: 600;
  font-size: var(--font-size-body);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
}

.ui-btn:hover {
  background: var(--color-accent-hover);
}

.ui-btn:disabled {
  opacity: 0.6;
  cursor: default;
}

.ui-btn.secondary {
  background: transparent;
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}

.ui-btn.secondary:hover {
  background: color-mix(in srgb, var(--color-surface) 92%, var(--color-text-primary));
}

.ui-btn.danger {
  background: var(--color-error);
}

.ui-btn.danger:hover {
  background: color-mix(in srgb, var(--color-error) 85%, black);
}

.ui-btn.sm {
  min-height: 36px;
  padding: 0 var(--space-sm);
  font-size: var(--font-size-caption);
}
</style>

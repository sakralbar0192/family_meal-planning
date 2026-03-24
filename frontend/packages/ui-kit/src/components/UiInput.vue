<script setup lang="ts">
const model = defineModel<string | number>({ default: '' });

const props = withDefaults(
  defineProps<{
    label?: string;
    placeholder?: string;
    type?: string;
    disabled?: boolean;
  }>(),
  {
    label: '',
    placeholder: '',
    type: 'text',
    disabled: false,
  },
);
</script>

<template>
  <label class="ui-field">
    <span v-if="props.label">{{ props.label }}</span>
    <input v-model="model" :type="props.type" :placeholder="props.placeholder" :disabled="props.disabled" />
  </label>
</template>

<style scoped>
.ui-field {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  font-size: var(--font-size-caption);
  color: var(--color-text-secondary);
}

.ui-field input {
  min-height: var(--input-min-height);
  padding: 0 var(--space-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: var(--color-text-primary);
  font-size: var(--font-size-body);
}

.ui-field input:disabled {
  opacity: 0.6;
}
</style>

<script setup lang="ts">
import type { VNode } from 'vue';
import { defineComponent } from 'vue';

defineProps<{
  factory?: (() => VNode) | null;
}>();

const Dyn = defineComponent({
  name: 'ShellVnodeDyn',
  props: {
    factory: { type: Function, required: true },
  },
  setup(props) {
    return () => (props.factory as () => VNode)();
  },
});
</script>

<template>
  <Dyn v-if="factory" :factory="factory" />
</template>

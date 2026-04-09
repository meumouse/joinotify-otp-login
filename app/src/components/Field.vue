<script setup>
defineProps({
  forId: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  helper: {
    type: String,
    default: '',
  },
});
</script>

<template>
  <div class="space-y-2">
    <label :for="forId" class="block text-sm font-semibold text-slate-700">
      {{ label }}
    </label>
    <slot />
    <p v-if="helper" class="text-xs leading-5 text-slate-500">
      {{ helper }}
    </p>
  </div>
</template>

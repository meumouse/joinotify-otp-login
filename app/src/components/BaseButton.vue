<script setup>
defineProps({
  kind: {
    type: String,
    default: 'primary',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    default: 'button',
  },
});

const classes = {
  primary: 'joinotify-otp-login__button text-white disabled:cursor-not-allowed disabled:opacity-60',
  secondary: 'joinotify-otp-login__button-secondary border border-slate-200 bg-white text-slate-700',
};
</script>

<template>
  <button
    :class="[
      'inline-flex w-full items-center justify-center px-5 py-3 text-sm font-semibold transition focus:outline-none',
      classes[kind] || classes.primary,
    ]"
    :disabled="disabled || loading"
    :type="type"
  >
    <slot />
  </button>
</template>

<script setup>
import { nextTick, ref } from 'vue';

/**
 * OTP input grid with keyboard navigation and paste support.
 *
 * @since 1.0.0
 * @type {Object}
 */
const props = defineProps({
  digits: {
    type: Array,
    required: true,
  },
  length: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(['update:digits', 'complete']);

const inputRefs = ref([]);

/**
 * Focus the requested OTP input after the DOM update cycle.
 *
 * @since 1.0.0
 * @param {HTMLElement[]} targets Input references.
 * @param {number} index Input index.
 * @return {void}
 */
function focusRef(targets, index) {
  nextTick(() => {
    const target = targets[index];
    if (target) {
      target.focus();
      target.select();
    }
  });
}

/**
 * Handle a digit input and advance focus when appropriate.
 *
 * @since 1.0.0
 * @param {HTMLElement[]} targets Input references.
 * @param {number} index Input index.
 * @param {Event} event Native input event.
 * @return {void}
 */
function onInput(targets, index, event) {
  const value = String(event.target.value || '').replace(/\D+/g, '').slice(-1);
  const nextDigits = [...props.digits];
  nextDigits[index] = value;
  emit('update:digits', nextDigits);
  event.target.value = value;

  if (value && index < props.length - 1) {
    focusRef(targets, index + 1);
  }

  if (nextDigits.every(Boolean)) {
    emit('complete');
  }
}

/**
 * Move focus back on backspace when the current digit is empty.
 *
 * @since 1.0.0
 * @param {HTMLElement[]} targets Input references.
 * @param {number} index Input index.
 * @param {KeyboardEvent} event Native keyboard event.
 * @return {void}
 */
function onKeydown(targets, index, event) {
  if (event.key === 'Backspace' && !props.digits[index] && index > 0) {
    focusRef(targets, index - 1);
  }
}

/**
 * Paste a full or partial OTP into the digit grid.
 *
 * @since 1.0.0
 * @param {HTMLElement[]} targets Input references.
 * @param {number} index Input index.
 * @param {ClipboardEvent} event Native paste event.
 * @return {void}
 */
function onPaste(targets, index, event) {
  const pasted = String(event.clipboardData?.getData('text') || '').replace(/\D+/g, '').slice(0, props.length);

  if (!pasted) {
    return;
  }

  event.preventDefault();
  const nextDigits = Array.from({ length: props.length }, (_, position) => pasted[position] || '');
  emit('update:digits', nextDigits);

  if (pasted.length === props.length) {
    emit('complete');
    return;
  }

  focusRef(targets, Math.min(index + pasted.length, props.length - 1));
}
</script>

<template>
  <div class="grid grid-cols-3 gap-2 sm:grid-cols-6 sm:gap-3">
    <input
      v-for="(_, index) in length"
      :key="index"
      :ref="(el) => (inputRefs[index] = el)"
      :value="digits[index]"
      class="joinotify-otp-login__input h-14 border border-slate-200 bg-slate-50 text-center text-xl font-semibold text-slate-900 outline-none transition placeholder:text-slate-400"
      type="text"
      inputmode="numeric"
      autocomplete="one-time-code"
      maxlength="1"
      :aria-label="`Code digit ${index + 1}`"
      @input="onInput(inputRefs, index, $event)"
      @keydown="onKeydown(inputRefs, index, $event)"
      @paste="onPaste(inputRefs, index, $event)"
    />
  </div>
</template>

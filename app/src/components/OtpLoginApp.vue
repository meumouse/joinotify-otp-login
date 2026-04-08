<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import FormCheckbox from './FormCheckbox.vue';

let intlUtilsPromise = null;

const props = defineProps({
  context: {
    type: String,
    default: 'myaccount',
  },
  defaultCountry: {
    type: String,
    default: 'br',
  },
  apiBaseUrl: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  showHeader: {
    type: Boolean,
    default: true,
  },
  otpLength: {
    type: Number,
    default: 6,
  },
  redirectUrl: {
    type: String,
    default: '/',
  },
  title: {
    type: String,
    default: '',
  },
  utilsUrl: {
    type: String,
    default: '',
  },
});

const steps = {
  phone: 'phone',
  otp: 'otp',
  password: 'password',
};

const scope = ref(null);
const phoneInput = ref(null);
const otpInputs = ref([]);
const otpPhone = ref('');
const visiblePhone = ref('');
const hiddenPhone = ref('');
const currentStep = ref(steps.phone);
const loading = ref(false);
const countdown = ref(60);
const resendEnabled = ref(false);
const resendTimer = ref(null);
const notice = ref({ type: 'info', message: '' });
const remember = ref(false);
const email = ref('');
const password = ref('');
const showPassword = ref(false);
const otpDigits = ref(Array.from({ length: props.otpLength }, () => ''));
const phoneIti = ref(null);
const apiBaseUrl = computed(() => props.apiBaseUrl || window.joinotifyOtpLogin.restUrl || '');
const useRestApi = computed(() => Boolean(window.joinotifyOtpLogin.restUrl));
const i18n = computed(() => window.joinotifyOtpLogin?.i18n || {});
const utilsUrl = computed(() => props.utilsUrl || window.joinotifyOtpLogin?.intlUtilsUrl || '');
const lostPasswordUrl = computed(() => window.joinotifyOtpLogin?.lostPasswordUrl || '#');
const theme = computed(() => window.joinotifyOtpLogin?.theme || {});
const primaryColor = computed(() => theme.value.primaryColor || '#4f46e5');
const borderRadius = computed(() => `${theme.value.borderRadius || 28}px`);

function hexToRgb(hex) {
  const value = String(hex || '').replace('#', '');

  if (value.length !== 6) {
    return '79, 70, 229';
  }

  const r = Number.parseInt(value.slice(0, 2), 16);
  const g = Number.parseInt(value.slice(2, 4), 16);
  const b = Number.parseInt(value.slice(4, 6), 16);

  return `${r}, ${g}, ${b}`;
}

const rootStyle = computed(() => ({
  '--joinotify-primary': primaryColor.value,
  '--joinotify-primary-rgb': hexToRgb(primaryColor.value),
  '--joinotify-radius': borderRadius.value,
  '--joinotify-soft-0': theme.value.palette?.['0'] || '#ffffff',
  '--joinotify-soft-50': theme.value.palette?.['50'] || '#eef2ff',
  '--joinotify-soft-100': theme.value.palette?.['100'] || '#e0e7ff',
  '--joinotify-soft-200': theme.value.palette?.['200'] || '#c7d2fe',
  '--joinotify-soft-700': theme.value.palette?.['700'] || '#4338ca',
  '--joinotify-soft-900': theme.value.palette?.['900'] || '#312e81',
}));

function t(key, fallback = '') {
  return i18n.value[key] || fallback;
}

function tWithCount(key, fallback, value) {
  return String(t(key, fallback)).replace('%d', String(value));
}

function requestUrl(pathOrAction) {
  if (useRestApi.value) {
    return `${apiBaseUrl.value}/${pathOrAction}`;
  }

  return window.joinotifyOtpLogin.ajaxUrl;
}

const otpJoined = computed(() => otpDigits.value.join(''));
const phonePreview = computed(() => otpPhone.value || hiddenPhone.value || visiblePhone.value);
const phoneFieldId = computed(() => `joinotify-phone-${props.context}`);
const emailFieldId = computed(() => `joinotify-email-${props.context}`);
const passwordFieldId = computed(() => `joinotify-password-${props.context}`);
const rememberFieldId = computed(() => `joinotify-remember-${props.context}`);

const noticeClasses = computed(() => {
  const base = 'rounded-2xl border px-4 py-3 text-sm leading-6';

  if (notice.value.type === 'success') {
    return `${base} border-emerald-200 bg-emerald-50 text-emerald-900`;
  }

  if (notice.value.type === 'error') {
    return `${base} border-rose-200 bg-rose-50 text-rose-900`;
  }

  return `${base} border-sky-200 bg-sky-50 text-sky-900`;
});

function normalizePhone(raw) {
  const value = String(raw || '').trim();
  const digits = value.replace(/\D+/g, '');

  if (!digits) {
    return '';
  }

  if (value.startsWith('+')) {
    return `+${digits}`;
  }

  const dialCodeMap = {
    br: '55',
    us: '1',
    ca: '1',
    pt: '351',
    es: '34',
    fr: '33',
    de: '49',
    it: '39',
    gb: '44',
    uk: '44',
    ar: '54',
    cl: '56',
    co: '57',
    mx: '52',
    pe: '51',
    uy: '598',
    py: '595',
  };

  return `+${dialCodeMap[props.defaultCountry.toLowerCase()] || '55'}${digits}`;
}

function setNotice(type, message) {
  notice.value = { type, message };
}

function clearNotice() {
  notice.value = { type: 'info', message: '' };
}

function setLoadingState(value) {
  loading.value = value;
}

function switchStep(step) {
  currentStep.value = step;
}

function resetOtpDigits() {
  otpDigits.value = Array.from({ length: props.otpLength }, () => '');
}

function focusOtp(index) {
  nextTick(() => {
    const target = otpInputs.value[index];

    if (target) {
      target.focus();
      target.select();
    }
  });
}

function syncPhoneFromInput() {
  const value = phoneInput.value ? phoneInput.value.value : visiblePhone.value;

  visiblePhone.value = value;
  hiddenPhone.value = normalizePhone(value);
}

function loadIntlUtils() {
  if (!utilsUrl.value) {
    return Promise.resolve();
  }

  if (!intlUtilsPromise) {
    intlUtilsPromise = import(/* @vite-ignore */ utilsUrl.value);
  }

  return intlUtilsPromise;
}

function initPhoneInput() {
  const input = phoneInput.value;

  if (!input || typeof window.intlTelInput !== 'function') {
    syncPhoneFromInput();
    return;
  }

  phoneIti.value = window.intlTelInput(input, {
    initialCountry: props.defaultCountry || 'br',
    nationalMode: false,
    formatOnDisplay: true,
    autoPlaceholder: 'aggressive',
    placeholderNumberType: 'MOBILE',
    containerClass: 'w-full',
    customPlaceholder: (selectedCountryPlaceholder) => selectedCountryPlaceholder || '',
    loadUtils: loadIntlUtils,
  });

  syncPhoneFromInput();
}

function readPhoneValue() {
  const intlUtils = window.intlTelInput?.utils || window.intlTelInputUtils;

  if (phoneIti.value && typeof phoneIti.value.getNumber === 'function' && intlUtils?.numberFormat) {
    const number = phoneIti.value.getNumber(intlUtils.numberFormat.E164);

    if (number) {
      return number;
    }
  }

  return normalizePhone(visiblePhone.value);
}

function fillOtpDigits(value) {
  const digits = String(value || '').replace(/\D+/g, '').slice(0, props.otpLength);

  otpDigits.value = Array.from({ length: props.otpLength }, (_, index) => digits[index] || '');

  if (digits.length < props.otpLength) {
    focusOtp(digits.length);
  }
}

async function requestOtp() {
  syncPhoneFromInput();
  const phone = readPhoneValue();

  if (!phone) {
    setNotice('error', t('invalidPhone', 'Digite um telefone válido com DDI.'));
    return;
  }

  setLoadingState(true);
  clearNotice();

  try {
    const response = await window.fetch(requestUrl('request-code'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-WP-Nonce': window.joinotifyOtpLogin.nonce,
      },
      body: new URLSearchParams({
        ...(useRestApi.value ? { phone } : { action: 'joinotify_otp_request_code', nonce: window.joinotifyOtpLogin.legacyNonce, phone }),
      }),
    });

    const payload = await response.json();

    if (!payload.success) {
      setNotice('error', payload.data?.message || t('unexpectedError', 'Não foi possível concluir a solicitação agora. Tente novamente.'));
      return;
    }

    hiddenPhone.value = payload.data.phone || phone;
    otpPhone.value = payload.data.phone || phone;

    if (payload.data.nextStep === 'password') {
      setNotice('info', payload.data.message);
      switchStep(steps.password);
      return;
    }

    setNotice('success', payload.data.message);
    switchStep(steps.otp);
    resetOtpDigits();
    startCountdown();
    await nextTick();
    focusOtp(0);
  } catch (error) {
    setNotice('error', t('unexpectedError', 'Não foi possível concluir a solicitação agora. Tente novamente.'));
  } finally {
    setLoadingState(false);
  }
}

async function verifyOtp() {
  const otp = otpJoined.value;
  const phone = hiddenPhone.value || otpPhone.value;

  if (!phone || otp.length !== props.otpLength) {
    setNotice('error', t('invalidOtp', 'Digite o código de verificação recebido.'));
    focusOtp(otpDigits.value.findIndex((digit) => !digit));
    return;
  }

  if (loading.value) {
    return;
  }

  setLoadingState(true);

  try {
    const response = await window.fetch(requestUrl('verify-code'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-WP-Nonce': window.joinotifyOtpLogin.nonce,
      },
      body: new URLSearchParams({
        ...(useRestApi.value
          ? { phone, otp, remember: remember.value ? '1' : '0', redirect: props.redirectUrl }
          : {
              action: 'joinotify_otp_verify_code',
              nonce: window.joinotifyOtpLogin.legacyNonce,
              phone,
              otp,
              remember: remember.value ? '1' : '0',
              redirect: props.redirectUrl,
            }),
      }),
    });

    const payload = await response.json();

    if (!payload.success) {
      setNotice('error', payload.data?.message || t('unexpectedError', 'Não foi possível concluir a solicitação agora. Tente novamente.'));
      return;
    }

    window.location.href = payload.data.redirect;
  } catch (error) {
    setNotice('error', t('unexpectedError', 'Não foi possível concluir a solicitação agora. Tente novamente.'));
  } finally {
    setLoadingState(false);
  }
}

async function loginWithPassword() {
  if (!email.value || !password.value) {
    setNotice('error', t('missingCredentials', 'Preencha o e-mail e a senha.'));
    return;
  }

  setLoadingState(true);

  try {
    const response = await window.fetch(requestUrl('password-login'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-WP-Nonce': window.joinotifyOtpLogin.nonce,
      },
      body: new URLSearchParams({
        ...(useRestApi.value
          ? {
              email: email.value,
              password: password.value,
              remember: remember.value ? '1' : '0',
              redirect: props.redirectUrl,
            }
          : {
              action: 'joinotify_otp_password_login',
              nonce: window.joinotifyOtpLogin.legacyNonce,
              email: email.value,
              password: password.value,
              remember: remember.value ? '1' : '0',
              redirect: props.redirectUrl,
            }),
      }),
    });

    const payload = await response.json();

    if (!payload.success) {
      setNotice('error', payload.data?.message || t('unexpectedError', 'Não foi possível concluir a solicitação agora. Tente novamente.'));
      return;
    }

    window.location.href = payload.data.redirect;
  } catch (error) {
    setNotice('error', t('unexpectedError', 'Não foi possível concluir a solicitação agora. Tente novamente.'));
  } finally {
    setLoadingState(false);
  }
}

function startCountdown() {
  resendEnabled.value = false;
  countdown.value = 60;

  if (resendTimer.value) {
    clearInterval(resendTimer.value);
  }

  resendTimer.value = setInterval(() => {
    countdown.value -= 1;

    if (countdown.value <= 0) {
      resendEnabled.value = true;
      clearInterval(resendTimer.value);
      resendTimer.value = null;
    }
  }, 1000);
}

function handleOtpInput(index, event) {
  const value = String(event.target.value || '').replace(/\D+/g, '').slice(-1);
  otpDigits.value[index] = value;
  event.target.value = value;

  if (value && index < props.otpLength - 1) {
    focusOtp(index + 1);
  }

  if (otpDigits.value.every(Boolean)) {
    verifyOtp();
  }
}

function handleOtpKeydown(index, event) {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    focusOtp(index - 1);
  }
}

function handleOtpPaste(index, event) {
  const pasted = String(event.clipboardData?.getData('text') || '').replace(/\D+/g, '').slice(0, props.otpLength);

  if (!pasted) {
    return;
  }

  event.preventDefault();

  otpDigits.value = Array.from({ length: props.otpLength }, (_, position) => pasted[position] || '');

  if (pasted.length === props.otpLength) {
    verifyOtp();
    return;
  }

  focusOtp(Math.min(index + pasted.length, props.otpLength - 1));
}

function resendOtp() {
  requestOtp();
}

function setOtpInputRef(el, index) {
  otpInputs.value[index] = el;
}

onMounted(() => {
  initPhoneInput();
});

onBeforeUnmount(() => {
  if (resendTimer.value) {
    clearInterval(resendTimer.value);
  }
});
</script>

<template>
  <div
    ref="scope"
    :style="rootStyle"
    class="joinotify-otp-login__surface relative isolate w-full overflow-hidden border border-slate-200 bg-white/95 px-6 py-7 backdrop-blur sm:px-8 sm:py-9"
  >
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <div class="joinotify-otp-login__orb joinotify-otp-login__orb--primary absolute -right-24 -top-24 h-56 w-56 rounded-full blur-3xl"></div>
      <div class="joinotify-otp-login__orb joinotify-otp-login__orb--secondary absolute -bottom-24 -left-24 h-56 w-56 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 mx-auto flex w-full max-w-md flex-col gap-6">
      <div v-if="showHeader" class="space-y-2 text-center">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
          {{ t('panelEyebrow', 'Acesso seguro') }}
        </p>
        <h2 class="joinotify-otp-login__title text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
          {{ title || t('phoneTitle', 'Entrar com WhatsApp') }}
        </h2>
        <p v-if="description" class="joinotify-otp-login__description text-sm leading-6 text-slate-500 sm:text-base">
          {{ description }}
        </p>
      </div>

      <div v-if="notice.message" :class="noticeClasses">
        {{ notice.message }}
      </div>

      <section v-show="currentStep === steps.phone" class="space-y-5">
        <form class="space-y-5" @submit.prevent="requestOtp">
          <div class="space-y-2">
            <label :for="phoneFieldId" class="block text-sm font-semibold text-slate-700">
              {{ t('phoneLabel', 'Telefone') }}
            </label>
            <input
              :id="phoneFieldId"
              ref="phoneInput"
              v-model="visiblePhone"
              type="tel"
              class="joinotify-otp-login__input w-full border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400"
              autocomplete="tel"
              inputmode="tel"
              @blur="syncPhoneFromInput"
              @change="syncPhoneFromInput"
              @countrychange="syncPhoneFromInput"
              @input="syncPhoneFromInput"
            />
            <p class="text-xs leading-5 text-slate-500">
              {{ t('phoneHelper', 'Digite um telefone válido. O DDI será exibido automaticamente.') }}
            </p>
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <button
              class="joinotify-otp-login__button inline-flex items-center justify-center px-5 py-3 text-sm font-semibold text-white transition focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loading"
              type="submit"
            >
              {{ loading ? t('requestCodeLoading', 'Enviando...') : t('phoneAction', 'Entrar com WhatsApp') }}
            </button>
            <button
              class="joinotify-otp-login__button-secondary inline-flex items-center justify-center border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition focus:outline-none"
              type="button"
              @click="switchStep(steps.password)"
            >
              {{ t('useEmailPassword', 'Usar e-mail e senha') }}
            </button>
          </div>
        </form>
      </section>

      <section v-show="currentStep === steps.otp" class="space-y-5">
        <form class="space-y-5" @submit.prevent="verifyOtp">
          <input v-model="hiddenPhone" type="hidden" name="phone" />
          <input :value="redirectUrl" type="hidden" name="redirect" />
          <input :value="otpJoined" type="hidden" name="otp" />

          <div class="space-y-2 text-center">
            <h3 class="text-xl font-semibold tracking-tight text-slate-900">
              {{ t('enterCodeTitle', 'Digite o código de acesso') }}
            </h3>
            <p class="text-sm leading-6 text-slate-500">
              {{ tWithCount('enterCodeDescription', 'Informe o código de %d dígitos enviado para o seu WhatsApp.', props.otpLength) }}
            </p>
            <p class="text-sm font-medium text-slate-700">
              {{ phonePreview }}
            </p>
          </div>

          <div class="grid grid-cols-3 gap-2 sm:grid-cols-6 sm:gap-3">
            <input
              v-for="(_, index) in otpLength"
              :key="index"
              :ref="(el) => setOtpInputRef(el, index)"
              :value="otpDigits[index]"
              class="joinotify-otp-login__input h-14 border border-slate-200 bg-slate-50 text-center text-xl font-semibold text-slate-900 outline-none transition placeholder:text-slate-400"
              type="text"
              inputmode="numeric"
              autocomplete="one-time-code"
              maxlength="1"
              :aria-label="tWithCount('otpDigitLabel', 'Dígito %d do código', index + 1)"
              @input="handleOtpInput(index, $event)"
              @keydown="handleOtpKeydown(index, $event)"
              @paste="handleOtpPaste(index, $event)"
            />
          </div>

          <FormCheckbox
            v-model="remember"
            :id="rememberFieldId"
            :label="t('rememberMe', 'Lembrar de mim')"
            name="remember"
          />

          <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <template v-if="!resendEnabled">
              <span>{{ t('resendOtpLabel', 'Reenviar código em') }}</span>
              <span class="font-semibold text-slate-700">{{ countdown }}</span>
              <span>{{ t('secondsLabel', 'segundos') }}</span>
            </template>
            <button
              v-else
              class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
              type="button"
              @click="resendOtp"
            >
              {{ t('resendOtpButton', 'Reenviar código') }}
            </button>
          </div>

          <div class="grid gap-3">
            <button
              class="joinotify-otp-login__button inline-flex items-center justify-center px-5 py-3 text-sm font-semibold text-white transition focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loading"
              type="submit"
            >
              {{ loading ? t('verifyCodeLoading', 'Verificando...') : t('verifyCode', 'Verificar código') }}
            </button>
            <button
              class="joinotify-otp-login__button-secondary inline-flex items-center justify-center border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition focus:outline-none"
              type="button"
              @click="switchStep(steps.phone)"
            >
              {{ t('changePhone', 'Alterar número') }}
            </button>
          </div>
        </form>
      </section>

      <section v-show="currentStep === steps.password" class="space-y-5">
        <div class="text-center">
          <div class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
            {{ t('emailSeparator', 'Ou entre com e-mail') }}
          </div>
        </div>

        <form class="space-y-5" @submit.prevent="loginWithPassword">
          <div class="space-y-2">
            <label :for="emailFieldId" class="block text-sm font-semibold text-slate-700">
              {{ t('emailLabel', 'E-mail') }}
            </label>
            <input
              :id="emailFieldId"
              v-model="email"
              type="email"
              class="joinotify-otp-login__input w-full border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400"
              autocomplete="email"
              :placeholder="t('emailPlaceholder', 'Digite seu e-mail')"
            />
          </div>

          <div class="space-y-2">
            <label :for="passwordFieldId" class="block text-sm font-semibold text-slate-700">
              {{ t('passwordLabel', 'Senha') }}
            </label>
            <div class="relative">
              <input
                :id="passwordFieldId"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                class="joinotify-otp-login__input w-full border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm text-slate-900 outline-none transition placeholder:text-slate-400"
                autocomplete="current-password"
                :placeholder="t('passwordPlaceholder', 'Digite sua senha')"
              />
              <button
                class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-500 transition hover:text-slate-900"
                type="button"
                :aria-label="showPassword ? t('hidePassword', 'Ocultar senha') : t('showPassword', 'Mostrar senha')"
                @click="showPassword = !showPassword"
              >
                <svg
                  v-if="!showPassword"
                  aria-hidden="true"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M2.95862 12.951C2.68046 12.3479 2.68046 11.6523 2.95862 11.0492C4.53779 7.6253 7.99237 5.25 11.9999 5.25C16.0075 5.25 19.4621 7.62531 21.0413 11.0492C21.3194 11.6523 21.3194 12.3479 21.0413 12.951C19.4621 16.3749 16.0075 18.7502 11.9999 18.7502C7.99237 18.7502 4.53779 16.3749 2.95862 12.951Z"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                  />
                  <path
                    d="M15.625 12C15.625 14.002 14.002 15.625 12 15.625C9.99797 15.625 8.375 14.002 8.375 12C8.375 9.99797 9.99797 8.375 12 8.375C14.002 8.375 15.625 9.99797 15.625 12Z"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                  />
                </svg>
                <svg
                  v-else
                  aria-hidden="true"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M3 3L21 21"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-width="1.5"
                  />
                  <path
                    d="M10.58 10.58A3 3 0 0013.42 13.42"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                  />
                  <path
                    d="M9.88 5.08A10.4 10.4 0 0112 4.75C16.0075 4.75 19.4621 7.12531 21.0413 10.5492C21.3194 11.1523 21.3194 11.8479 21.0413 12.451C20.3337 14.0046 19.234 15.31 17.866 16.25"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                  />
                  <path
                    d="M6.12 6.12C4.57872 7.32617 3.30035 8.93047 2.95862 10.0492C2.68046 10.6523 2.68046 11.3479 2.95862 11.951C4.53779 15.3749 7.99237 17.7502 11.9999 17.7502C13.5083 17.7502 14.9295 17.4353 16.16 16.87"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                  />
                </svg>
              </button>
            </div>
          </div>

          <FormCheckbox
            v-model="remember"
            :id="rememberFieldId"
            :label="t('rememberMe', 'Lembrar de mim')"
            name="remember"
          />

          <div class="grid gap-3 sm:grid-cols-2">
            <button
              class="joinotify-otp-login__button inline-flex items-center justify-center px-5 py-3 text-sm font-semibold text-white transition focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loading"
              type="submit"
            >
              {{ loading ? t('signInLoading', 'Processando...') : t('signIn', 'Entrar') }}
            </button>
            <button
              class="joinotify-otp-login__button-secondary inline-flex items-center justify-center border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition focus:outline-none"
              type="button"
              @click="switchStep(steps.phone)"
            >
              {{ t('backToWhatsapp', 'Voltar ao WhatsApp') }}
            </button>
          </div>

          <p class="text-center text-sm text-slate-500">
            <a
              class="font-semibold text-indigo-600 transition hover:text-indigo-500"
              :href="lostPasswordUrl"
            >
              {{ t('forgotPassword', 'Esqueceu a senha?') }}
            </a>
          </p>
        </form>
      </section>
    </div>
  </div>
</template>

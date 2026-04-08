<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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
    default: 'Sign in with WhatsApp',
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
const otpDigits = ref(Array.from({ length: props.otpLength }, () => ''));
const phoneIti = ref(null);
const apiBaseUrl = computed(() => props.apiBaseUrl || window.joinotifyOtpLogin.restUrl || '');
const useRestApi = computed(() => Boolean(window.joinotifyOtpLogin.restUrl));

function requestUrl(pathOrAction) {
  if (useRestApi.value) {
    return `${apiBaseUrl.value}/${pathOrAction}`;
  }

  return window.joinotifyOtpLogin.ajaxUrl;
}

const otpJoined = computed(() => otpDigits.value.join(''));
const phonePreview = computed(() => otpPhone.value || hiddenPhone.value || visiblePhone.value);

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
  hiddenPhone.value = normalizePhone(phoneInput.value ? phoneInput.value.value : visiblePhone.value);
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
    separateDialCode: true,
  });

  syncPhoneFromInput();
}

function readPhoneValue() {
  if (phoneIti.value && typeof phoneIti.value.getNumber === 'function') {
    const number = phoneIti.value.getNumber(window.intlTelInputUtils ? window.intlTelInputUtils.numberFormat.E164 : undefined);

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
    setNotice('error', window.joinotifyOtpLogin?.i18n?.invalidPhone || 'Enter a valid phone number with country code.');
    return;
  }

  setLoadingState(true);

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
      setNotice('error', payload.data?.message || window.joinotifyOtpLogin?.i18n?.unexpectedError || 'Unexpected error');
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
    setNotice('error', window.joinotifyOtpLogin?.i18n?.unexpectedError || 'Unexpected error');
  } finally {
    setLoadingState(false);
  }
}

async function verifyOtp() {
  const otp = otpJoined.value;
  const phone = hiddenPhone.value || otpPhone.value;

  if (!phone || otp.length !== props.otpLength) {
    setNotice('error', window.joinotifyOtpLogin?.i18n?.invalidOtp || 'Enter the verification code you received.');
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
      setNotice('error', payload.data?.message || window.joinotifyOtpLogin?.i18n?.unexpectedError || 'Unexpected error');
      return;
    }

    window.location.href = payload.data.redirect;
  } catch (error) {
    setNotice('error', window.joinotifyOtpLogin?.i18n?.unexpectedError || 'Unexpected error');
  } finally {
    setLoadingState(false);
  }
}

async function loginWithPassword() {
  if (!email.value || !password.value) {
    setNotice('error', window.joinotifyOtpLogin?.i18n?.unexpectedError || 'Unexpected error');
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
      setNotice('error', payload.data?.message || window.joinotifyOtpLogin?.i18n?.unexpectedError || 'Unexpected error');
      return;
    }

    window.location.href = payload.data.redirect;
  } catch (error) {
    setNotice('error', window.joinotifyOtpLogin?.i18n?.unexpectedError || 'Unexpected error');
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
  <div ref="scope" class="joinotify-otp-login__panel">
    <div class="joinotify-otp-login__header">
      <p class="joinotify-otp-login__eyebrow">Joinotify OTP Login</p>
      <h2 class="joinotify-otp-login__title">{{ title }}</h2>
      <p v-if="description" class="joinotify-otp-login__description">{{ description }}</p>
    </div>

    <div v-if="notice.message" class="joinotify-otp-notice" :class="`joinotify-otp-notice--${notice.type}`">
      {{ notice.message }}
    </div>

    <section v-show="currentStep === steps.phone" class="joinotify-otp-step">
      <form class="joinotify-otp-login__form" @submit.prevent="requestOtp">
        <label class="joinotify-phone-field">
          <span>Phone <span aria-hidden="true">*</span></span>
          <input
            ref="phoneInput"
            v-model="visiblePhone"
            type="tel"
            class="input-text joinotify-phone-field__visible"
            autocomplete="tel"
            inputmode="tel"
            placeholder="55 11 99999-9999"
            @blur="syncPhoneFromInput"
            @change="syncPhoneFromInput"
            @keyup="syncPhoneFromInput"
          />
          <input v-model="hiddenPhone" type="hidden" name="phone" data-phone-hidden />
          <small class="joinotify-phone-field__helper">
            Use the full phone number. We also accept matches without DDI and DDD.
          </small>
        </label>

        <div class="joinotify-otp-login__actions">
          <button class="button alt" :disabled="loading" type="submit">
            {{ loading ? 'Sending...' : 'Receive code' }}
          </button>
          <button class="button button-secondary" type="button" @click="switchStep(steps.password)">
            Use email and password
          </button>
        </div>
      </form>
    </section>

    <section v-show="currentStep === steps.otp" class="joinotify-otp-step">
      <form class="joinotify-otp-login__form" @submit.prevent="verifyOtp">
        <input v-model="hiddenPhone" type="hidden" name="phone" />
        <input :value="redirectUrl" type="hidden" name="redirect" />
        <input :value="otpJoined" type="hidden" name="otp" />

        <div class="joinotify-otp-login__otp-header">
          <h3 class="joinotify-otp-login__subtitle">Enter the access code</h3>
          <p class="joinotify-otp-login__description">
            Type the {{ otpLength }}-digit code sent to your WhatsApp.
          </p>
          <p class="joinotify-otp-login__phone-preview">{{ phonePreview }}</p>
        </div>

        <div class="joinotify-otp-code-grid">
          <input
            v-for="(_, index) in otpLength"
            :key="index"
            :ref="(el) => setOtpInputRef(el, index)"
            :value="otpDigits[index]"
            class="otp-input-item"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="1"
            @input="handleOtpInput(index, $event)"
            @keydown="handleOtpKeydown(index, $event)"
            @paste="handleOtpPaste(index, $event)"
          />
        </div>

        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
          <input v-model="remember" class="woocommerce-form__input woocommerce-form__input-checkbox" type="checkbox" value="1" />
          <span>Remember me</span>
        </label>

        <div class="joinotify-otp-login__resend">
          <template v-if="!resendEnabled">
            <span class="joinotify-otp-login__resend-label">Resend code in</span>
            <span class="joinotify-otp-login__resend-countdown">{{ countdown }}</span>
            <span>seconds</span>
          </template>
          <button v-else type="button" class="button button-link request-new-otp" @click="resendOtp">
            Resend code
          </button>
        </div>

        <div class="joinotify-otp-login__actions joinotify-otp-login__actions--stacked">
          <button class="button alt" :disabled="loading" type="submit">
            {{ loading ? 'Verifying...' : 'Verify code' }}
          </button>
          <button class="button button-secondary" type="button" @click="switchStep(steps.phone)">
            Change phone number
          </button>
        </div>
      </form>
    </section>

    <section v-show="currentStep === steps.password" class="joinotify-otp-step">
      <div class="joinotify-otp-login__separator">Sign in with email</div>

      <form class="joinotify-otp-login__form" @submit.prevent="loginWithPassword">
        <label class="form-row form-row-wide">
          <span>Email <span aria-hidden="true">*</span></span>
          <input v-model="email" type="email" class="input-text" autocomplete="email" />
        </label>

        <label class="form-row form-row-wide">
          <span>Password <span aria-hidden="true">*</span></span>
          <input v-model="password" type="password" class="input-text" autocomplete="current-password" />
        </label>

        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
          <input v-model="remember" class="woocommerce-form__input woocommerce-form__input-checkbox" type="checkbox" value="1" />
          <span>Remember me</span>
        </label>

        <div class="joinotify-otp-login__actions">
          <button class="button alt" :disabled="loading" type="submit">
            {{ loading ? 'Processing...' : 'Sign in' }}
          </button>
          <button class="button button-secondary" type="button" @click="switchStep(steps.phone)">
            Back to phone
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

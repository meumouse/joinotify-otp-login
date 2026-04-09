import intlTelInput from 'intl-tel-input';
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';

export function useOtpLogin(props) {
  const steps = { phone: 'phone', otp: 'otp', password: 'password' };

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
  const lostPasswordUrl = computed(() => window.joinotifyOtpLogin?.lostPasswordUrl || '#');

  function t(key, fallback = '') { return i18n.value[key] || fallback; }
  function tWithCount(key, fallback, value) { return String(t(key, fallback)).replace('%d', String(value)); }
  function requestUrl(pathOrAction) { return useRestApi.value ? `${apiBaseUrl.value}/${pathOrAction}` : window.joinotifyOtpLogin.ajaxUrl; }
  function setNotice(type, message) { notice.value = { type, message }; }
  function clearNotice() { notice.value = { type: 'info', message: '' }; }
  function setLoadingState(value) { loading.value = value; }
  function switchStep(step) { currentStep.value = step; }
  function resetOtpDigits() { otpDigits.value = Array.from({ length: props.otpLength }, () => ''); }

  function focusOtp(index) {
    nextTick(() => {
      const target = otpInputs.value[index];
      if (target) { target.focus(); target.select(); }
    });
  }

  function syncPhoneFromInput() {
    const value = phoneInput.value ? phoneInput.value.value : visiblePhone.value;
    visiblePhone.value = value;
    hiddenPhone.value = value ? `+${String(value).replace(/\D+/g, '')}` : '';
  }

  function initPhoneInput() {
    const input = phoneInput.value;
    if (!input) { syncPhoneFromInput(); return; }
    phoneIti.value = intlTelInput(input, {
      initialCountry: props.defaultCountry || 'br',
      nationalMode: false,
      formatOnDisplay: true,
      autoPlaceholder: 'aggressive',
      placeholderNumberType: 'MOBILE',
      containerClass: 'w-full',
      loadUtils: () => import('intl-tel-input/utils'),
    });
    syncPhoneFromInput();
  }

  function readPhoneValue() {
    if (phoneIti.value && typeof phoneIti.value.getNumber === 'function') {
      const number = phoneIti.value.getNumber();
      if (number) return number;
    }
    return hiddenPhone.value || visiblePhone.value;
  }

  function fillOtpDigits(value) {
    const digits = String(value || '').replace(/\D+/g, '').slice(0, props.otpLength);
    otpDigits.value = Array.from({ length: props.otpLength }, (_, index) => digits[index] || '');
    if (digits.length < props.otpLength) focusOtp(digits.length);
  }

  function startCountdown() {
    resendEnabled.value = false;
    countdown.value = 60;
    if (resendTimer.value) clearInterval(resendTimer.value);
    resendTimer.value = setInterval(() => {
      countdown.value -= 1;
      if (countdown.value <= 0) {
        resendEnabled.value = true;
        clearInterval(resendTimer.value);
        resendTimer.value = null;
      }
    }, 1000);
  }

  onBeforeUnmount(() => {
    if (resendTimer.value) clearInterval(resendTimer.value);
  });

  return {
    steps,
    phoneInput,
    otpInputs,
    otpPhone,
    visiblePhone,
    hiddenPhone,
    currentStep,
    loading,
    countdown,
    resendEnabled,
    notice,
    remember,
    email,
    password,
    showPassword,
    otpDigits,
    phoneIti,
    lostPasswordUrl,
    t,
    tWithCount,
    requestUrl,
    setNotice,
    clearNotice,
    setLoadingState,
    switchStep,
    resetOtpDigits,
    focusOtp,
    syncPhoneFromInput,
    initPhoneInput,
    readPhoneValue,
    fillOtpDigits,
    startCountdown,
  };
}

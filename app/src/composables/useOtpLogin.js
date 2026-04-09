import intlTelInput from 'intl-tel-input';
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';

/**
 * Encapsulate OTP login state and behavior for the Vue app.
 *
 * @since 1.0.0
 * @param {Object} props Component props passed from the root component.
 * @return {Object} Reactive state and helper methods.
 */
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

  /**
   * Resolve a translated string with an optional fallback.
   *
   * @since 1.0.0
   * @param {string} key Translation key.
   * @param {string} fallback Fallback text.
   * @return {string} Localized text.
   */
  function t(key, fallback = '') { return i18n.value[key] || fallback; }

  /**
   * Resolve a translated string that contains a numeric placeholder.
   *
   * @since 1.0.0
   * @param {string} key Translation key.
   * @param {string} fallback Fallback text.
   * @param {number} value Numeric value to inject.
   * @return {string} Localized text with the count applied.
   */
  function tWithCount(key, fallback, value) { return String(t(key, fallback)).replace('%d', String(value)); }

  /**
   * Build the request URL according to the active transport.
   *
   * @since 1.0.0
   * @param {string} pathOrAction REST path or AJAX action.
   * @return {string} Endpoint URL.
   */
  function requestUrl(pathOrAction) { return useRestApi.value ? `${apiBaseUrl.value}/${pathOrAction}` : window.joinotifyOtpLogin.ajaxUrl; }

  /**
   * Store a feedback message in reactive state.
   *
   * @since 1.0.0
   * @param {string} type Notice type.
   * @param {string} message Notice text.
   * @return {void}
   */
  function setNotice(type, message) { notice.value = { type, message }; }

  /**
   * Clear the current notice message.
   *
   * @since 1.0.0
   * @return {void}
   */
  function clearNotice() { notice.value = { type: 'info', message: '' }; }

  /**
   * Toggle the loading indicator.
   *
   * @since 1.0.0
   * @param {boolean} value Loading state.
   * @return {void}
   */
  function setLoadingState(value) { loading.value = value; }

  /**
   * Move the widget to the requested login step.
   *
   * @since 1.0.0
   * @param {string} step Step name.
   * @return {void}
   */
  function switchStep(step) { currentStep.value = step; }

  /**
   * Reset the OTP array to blank digits.
   *
   * @since 1.0.0
   * @return {void}
   */
  function resetOtpDigits() { otpDigits.value = Array.from({ length: props.otpLength }, () => ''); }

  /**
   * Focus a specific OTP input after the DOM updates.
   *
   * @since 1.0.0
   * @param {number} index Input index.
   * @return {void}
   */
  function focusOtp(index) {
    nextTick(() => {
      const target = otpInputs.value[index];
      if (target) { target.focus(); target.select(); }
    });
  }

  /**
   * Sync the visible phone input into the normalized hidden field.
   *
   * @since 1.0.0
   * @return {void}
   */
  function syncPhoneFromInput() {
    const value = phoneInput.value ? phoneInput.value.value : visiblePhone.value;
    visiblePhone.value = value;
    hiddenPhone.value = value ? `+${String(value).replace(/\D+/g, '')}` : '';
  }

  /**
   * Initialize the intl-tel-input widget when the field is available.
   *
   * @since 1.0.0
   * @return {void}
   */
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

  /**
   * Read the best available phone value for submission.
   *
   * @since 1.0.0
   * @return {string} Normalized phone number.
   */
  function readPhoneValue() {
    if (phoneIti.value && typeof phoneIti.value.getNumber === 'function') {
      const number = phoneIti.value.getNumber();
      if (number) return number;
    }
    return hiddenPhone.value || visiblePhone.value;
  }

  /**
   * Fill the OTP state from a pasted or autofilled value.
   *
   * @since 1.0.0
   * @param {string} value OTP value.
   * @return {void}
   */
  function fillOtpDigits(value) {
    const digits = String(value || '').replace(/\D+/g, '').slice(0, props.otpLength);
    otpDigits.value = Array.from({ length: props.otpLength }, (_, index) => digits[index] || '');
    if (digits.length < props.otpLength) focusOtp(digits.length);
  }

  /**
   * Start the resend-code countdown timer.
   *
   * @since 1.0.0
   * @return {void}
   */
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

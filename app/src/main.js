import { createApp } from 'vue';
import OtpLoginApp from './components/OtpLoginApp.vue';
import './style.css';

/**
 * WordPress i18n fallback used when the global helper is unavailable.
 *
 * @since 1.0.0
 * @type {Function}
 */
const __ = window.wp?.i18n?.__ ?? ((text) => text);

/**
 * Read the trimmed text content from the first matching element.
 *
 * @since 1.0.0
 * @param {HTMLElement} el Root element.
 * @param {string} selector CSS selector to resolve.
 * @return {string} Trimmed text content or an empty string.
 */
function readText(el, selector) {
  const target = el.querySelector(selector);
  return target ? target.textContent.trim() : '';
}

/**
 * Mount the Vue login application on a single DOM root.
 *
 * @since 1.0.0
 * @param {HTMLElement} root Mount target.
 * @return {void}
 */
function mountOtpLoginApp(root) {
  const context = root.dataset.context || 'myaccount';
  const otpLength = Number.parseInt(root.dataset.otpLength || '6', 10) || 6;
  const defaultCountry = root.dataset.defaultCountry || 'br';
  const redirectUrl = root.dataset.redirectUrl || window.location.href;
  const showHeader = root.dataset.showHeader === '1';
  const apiBaseUrl = window.joinotifyOtpLogin.restUrl || window.joinotifyOtpLogin.ajaxUrl;

  const app = createApp(OtpLoginApp, {
    context,
    otpLength,
    defaultCountry,
    apiBaseUrl,
    redirectUrl,
    showHeader,
    title: root.dataset.title || readText(root, '.joinotify-otp-login__title'),
    description: root.dataset.description || readText(root, '.joinotify-otp-login__description'),
  });

  app.config.globalProperties.__ = __;
  app.mount(root);
}

/**
 * Mount every OTP login instance present on the page.
 *
 * @since 1.0.0
 * @return {void}
 */
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-joinotify-otp-login]').forEach(mountOtpLoginApp);
});

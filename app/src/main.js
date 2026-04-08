import { createApp } from 'vue';
import OtpLoginApp from './components/OtpLoginApp.vue';
import './style.css';

function readText(el, selector) {
  const target = el.querySelector(selector);
  return target ? target.textContent.trim() : '';
}

function mountOtpLoginApp(root) {
  const context = root.dataset.context || 'myaccount';
  const otpLength = Number.parseInt(root.dataset.otpLength || '6', 10) || 6;
  const defaultCountry = root.dataset.defaultCountry || 'br';
  const redirectUrl = root.dataset.redirectUrl || window.location.href;
  const apiBaseUrl = window.joinotifyOtpLogin.restUrl || window.joinotifyOtpLogin.ajaxUrl;

  const app = createApp(OtpLoginApp, {
    context,
    otpLength,
    defaultCountry,
    apiBaseUrl,
    redirectUrl,
    title: readText(root, '.joinotify-otp-login__title'),
    description: readText(root, '.joinotify-otp-login__description'),
  });

  app.mount(root);
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-joinotify-otp-login]').forEach(mountOtpLoginApp);
});

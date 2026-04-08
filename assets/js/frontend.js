(function ($) {
    'use strict';

    /**
     * Global configuration injected by the plugin on the frontend.
     *
     * @type {Object}
     * @since 1.0.0
     */
    var config = window.joinotifyOtpLogin || {};

    /**
     * Initializes the phone input widget and keeps the hidden field synced with the formatted value.
     *
     * @since 1.0.0
     * @param {Document|Element} context Container used to scope the query for phone inputs.
     * @return {void}
     */
    function initIntlTelInputs(context) {
        var telInputs = context.querySelectorAll('[data-phone-visible]');

        function digitsOnly(value) {
            return String(value || '').replace(/\D+/g, '');
        }

        function getDefaultDialCode() {
            var country = String(config.defaultCountry || 'br').toLowerCase();
            var map = {
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
                py: '595'
            };

            return map[country] || '55';
        }

        function getDefaultCountryCode() {
            return String(config.defaultCountry || 'br').toLowerCase();
        }

        function buildPhoneValue(input, iti) {
            var raw = String(input.value || '').trim();
            var digits = digitsOnly(raw);
            var defaultCountry = getDefaultCountryCode();

            if (iti && typeof iti.getSelectedCountryData === 'function') {
                var selectedCountry = iti.getSelectedCountryData() || {};

                if (selectedCountry.iso2 && String(selectedCountry.iso2).toLowerCase() === defaultCountry) {
                    if (typeof iti.getNumber === 'function') {
                        var intlValue = iti.getNumber(window.intlTelInputUtils ? window.intlTelInputUtils.numberFormat.E164 : undefined);

                        if (intlValue) {
                            return intlValue;
                        }
                    }
                }
            }

            if (raw.charAt(0) === '+') {
                return '+' + digits;
            }

            if (digits) {
                return '+' + getDefaultDialCode() + digits;
            }

            return '';
        }

        telInputs.forEach(function (input) {
            if (input.dataset.intlReady === '1') {
                return;
            }

            var form = input.closest('form');
            var hiddenInput = form ? form.querySelector('[data-phone-hidden]') : null;

            // The intl-tel-input plugin is optional; use the raw input value if it is not present.
            var iti = window.intlTelInput ? window.intlTelInput(input, {
                initialCountry: config.defaultCountry || 'br',
                nationalMode: false,
                formatOnDisplay: true,
                autoPlaceholder: 'aggressive',
                separateDialCode: true
            }) : null;

            input.dataset.intlReady = '1';

            var syncPhone = function () {
                var value = buildPhoneValue(input, iti);

                if (hiddenInput) {
                    hiddenInput.value = value;
                }
            };

            input.addEventListener('blur', syncPhone);
            input.addEventListener('change', syncPhone);
            input.addEventListener('keyup', syncPhone);
            syncPhone();
        });
    }

    /**
     * Displays a notice message inside the current login/register scope.
     *
     * @since 1.0.0
     * @param {Element} scope Container that owns the notice element.
     * @param {string} type Notice modifier suffix, such as error, success, or info.
     * @param {string} message Message to display. An empty value hides the notice.
     * @return {void}
     */
    function setMessage(scope, type, message) {
        var notice = scope.querySelector('[data-login-notice]');

        if (!notice) {
            return;
        }

        notice.className = 'joinotify-otp-notice joinotify-otp-notice--' + type;
        notice.textContent = message || '';
        notice.hidden = !message;
    }

    /**
     * Toggles the submit button state and preserves the original label.
     *
     * @since 1.0.0
     * @param {HTMLFormElement|Element} form Form that contains the submit button.
     * @param {boolean} isLoading Whether the form is in a loading state.
     * @param {string} label Label to show while the request is pending.
     * @return {void}
     */
    function setLoading(form, isLoading, label) {
        var submit = form.querySelector('[type="submit"]');

        if (!submit) {
            return;
        }

        if (!submit.dataset.originalLabel) {
            submit.dataset.originalLabel = submit.textContent;
        }

        submit.disabled = isLoading;
        submit.textContent = isLoading ? label : submit.dataset.originalLabel;
    }

    /**
     * Shows a single step in the multi-step login flow.
     *
     * @since 1.0.0
     * @param {Element} scope Current login container.
     * @param {string} step Step name to reveal, for example phone, otp, or password.
     * @return {void}
     */
    function switchStep(scope, step) {
        scope.querySelectorAll('[data-login-step]').forEach(function (panel) {
            panel.hidden = panel.dataset.loginStep !== step;
        });
    }

    /**
     * Sends a nonce-protected AJAX request to the WordPress backend.
     *
     * @since 1.0.0
     * @param {Object} data Payload sent to admin-ajax.php.
     * @return {jqXHR} jQuery AJAX promise.
     */
    function ajaxRequest(data) {
        return $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: $.extend({}, data, {
                nonce: config.nonce
            })
        });
    }

    /**
     * Wires the login flow events for one login container.
     *
     * @since 1.0.0
     * @param {Element} scope Login container that includes the phone, OTP, and password steps.
     * @return {void}
     */
    function bindPhoneFlow(scope) {
        var phoneForm = scope.querySelector('[data-phone-form]');
        var otpForm = scope.querySelector('[data-otp-form]');
        var passwordForm = scope.querySelector('[data-password-form]');
        var phoneHiddenInputs = scope.querySelectorAll('[data-phone-hidden]');

        if (!phoneForm || !otpForm || !passwordForm) {
            return;
        }

        phoneForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var phoneHidden = phoneForm.querySelector('[data-phone-hidden]');
            var visiblePhone = phoneForm.querySelector('[data-phone-visible]');

            if (phoneHidden && (!phoneHidden.value || phoneHidden.value.charAt(0) !== '+')) {
                phoneHidden.value = visiblePhone ? buildPhoneValue(visiblePhone, null) : '';
            }

            var phone = phoneHidden ? phoneHidden.value : '';

            if (!phone) {
                setMessage(scope, 'error', config.i18n.invalidPhone);
                return;
            }

            // Prevent duplicate OTP requests while the backend is processing the phone number.
            setLoading(phoneForm, true, config.i18n.sending);

            ajaxRequest({
                action: 'joinotify_otp_request_code',
                phone: phone
            }).done(function (response) {
                if (!response.success) {
                    setMessage(scope, 'error', response.data && response.data.message ? response.data.message : config.i18n.unexpectedError);
                    return;
                }

                phoneHiddenInputs.forEach(function (hidden) {
                    hidden.value = response.data.phone || phone;
                });

                setMessage(scope, response.data.nextStep === 'password' ? 'error' : 'success', response.data.message);

                if (response.data.nextStep === 'otp') {
                    switchStep(scope, 'otp');
                    if (otpForm.querySelector('input[name="otp"]')) {
                        otpForm.querySelector('input[name="otp"]').focus();
                    }
                } else if (response.data.nextStep === 'password') {
                    switchStep(scope, 'password');
                    if (passwordForm.querySelector('input[name="email"]')) {
                        passwordForm.querySelector('input[name="email"]').focus();
                    }
                }
            }).fail(function () {
                setMessage(scope, 'error', config.i18n.unexpectedError);
            }).always(function () {
                setLoading(phoneForm, false, config.i18n.sending);
            });
        });

        otpForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var otpField = otpForm.querySelector('input[name="otp"]');
            var otp = otpField ? otpField.value : '';

            if (!otp) {
                setMessage(scope, 'error', config.i18n.invalidOtp);
                return;
            }

            // Verification can end in a redirect, so keep the form locked until the response returns.
            setLoading(otpForm, true, config.i18n.verifying);

            ajaxRequest({
                action: 'joinotify_otp_verify_code',
                phone: otpForm.querySelector('[data-phone-hidden]').value,
                otp: otp,
                remember: otpForm.querySelector('input[name="remember"]') && otpForm.querySelector('input[name="remember"]').checked ? 1 : 0,
                redirect: otpForm.querySelector('input[name="redirect"]').value
            }).done(function (response) {
                if (!response.success) {
                    setMessage(scope, 'error', response.data && response.data.message ? response.data.message : config.i18n.unexpectedError);
                    return;
                }

                setMessage(scope, 'success', response.data.message);
                window.location.href = response.data.redirect;
            }).fail(function () {
                setMessage(scope, 'error', config.i18n.unexpectedError);
            }).always(function () {
                setLoading(otpForm, false, config.i18n.verifying);
            });
        });

        passwordForm.addEventListener('submit', function (event) {
            event.preventDefault();
            // Password login follows the same loading pattern as the OTP flow.
            setLoading(passwordForm, true, config.i18n.loading);

            ajaxRequest({
                action: 'joinotify_otp_password_login',
                email: passwordForm.querySelector('input[name="email"]').value,
                password: passwordForm.querySelector('input[name="password"]').value,
                remember: passwordForm.querySelector('input[name="remember"]') && passwordForm.querySelector('input[name="remember"]').checked ? 1 : 0,
                redirect: passwordForm.querySelector('input[name="redirect"]').value
            }).done(function (response) {
                if (!response.success) {
                    setMessage(scope, 'error', response.data && response.data.message ? response.data.message : config.i18n.unexpectedError);
                    return;
                }

                setMessage(scope, 'success', response.data.message);
                window.location.href = response.data.redirect;
            }).fail(function () {
                setMessage(scope, 'error', config.i18n.unexpectedError);
            }).always(function () {
                setLoading(passwordForm, false, config.i18n.loading);
            });
        });

        scope.querySelectorAll('[data-switch-step]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                // Switching between steps is handled entirely on the client side.
                switchStep(scope, button.dataset.switchStep);
                setMessage(scope, 'info', '');
            });
        });
    }

    /**
     * Binds registration submit handlers and reports backend feedback inline.
     *
     * @since 1.0.0
     * @param {Document|Element} context Container used to find register forms.
     * @return {void}
     */
    function bindRegisterForms(context) {
        context.querySelectorAll('.joinotify-otp-register-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                // Registration also disables the submit button while the request is pending.
                setLoading(form, true, config.i18n.loading);

                ajaxRequest({
                    action: 'joinotify_otp_register_user',
                    username: form.querySelector('input[name="username"]') ? form.querySelector('input[name="username"]').value : '',
                    email: form.querySelector('input[name="email"]').value,
                    password: form.querySelector('input[name="password"]').value,
                    phone: form.querySelector('[data-phone-hidden]').value,
                    redirect: form.querySelector('input[name="redirect"]').value
                }).done(function (response) {
                    var scope = form.closest('.joinotify-otp-register');
                    var notice = scope ? scope.querySelector('[data-register-notice]') : null;

                    if (notice) {
                        notice.className = response.success ? 'joinotify-otp-notice joinotify-otp-notice--success' : 'joinotify-otp-notice joinotify-otp-notice--error';
                        notice.textContent = response.data && response.data.message ? response.data.message : config.i18n.unexpectedError;
                        notice.hidden = false;
                    }

                    if (response.success) {
                        window.location.href = response.data.redirect;
                    }
                }).fail(function () {
                    var scope = form.closest('.joinotify-otp-register');
                    var notice = scope ? scope.querySelector('[data-register-notice]') : null;

                    if (notice) {
                        notice.className = 'joinotify-otp-notice joinotify-otp-notice--error';
                        notice.textContent = config.i18n.unexpectedError;
                        notice.hidden = false;
                    }
                }).always(function () {
                    setLoading(form, false, config.i18n.loading);
                });
            });
        });
    }

    /**
     * Bootstraps the frontend UI after the DOM is ready.
     *
     * @since 1.0.0
     * @return {void}
     */
    document.addEventListener('DOMContentLoaded', function () {
        initIntlTelInputs(document);

        document.querySelectorAll('.joinotify-otp-login').forEach(function (scope) {
            bindPhoneFlow(scope);
        });

        bindRegisterForms(document);
    });
})(jQuery);

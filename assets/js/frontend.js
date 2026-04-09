(function ($) {
    'use strict';

    /**
     * Shared runtime configuration injected by WordPress.
     *
     * @since 1.0.0
     * @type {Object}
     */
    var config = window.joinotifyOtpLogin || {};

    /**
     * Strip everything except digits from a phone or OTP string.
     *
     * @since 1.0.0
     * @param {string} value Input value.
     * @return {string} Digits-only string.
     */
    function digitsOnly(value) {
        return String(value || '').replace(/\D+/g, '');
    }

    /**
     * Resolve the OTP length from the current scope or the global config.
     *
     * @since 1.0.0
     * @param {HTMLElement} scope Login widget root element.
     * @return {number} OTP length.
     */
    function getOtpLength(scope) {
        var parsed = parseInt(scope && scope.dataset ? scope.dataset.otpLength : '', 10);

        if (Number.isFinite(parsed) && parsed > 0) {
            return parsed;
        }

        parsed = parseInt(config.otpLength || 6, 10);

        return Number.isFinite(parsed) && parsed > 0 ? parsed : 6;
    }

    /**
     * Map a country code to a default dial prefix.
     *
     * @since 1.0.0
     * @return {string} Dial prefix without the plus sign.
     */
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

    /**
     * Build the phone value that should be submitted to the backend.
     *
     * @since 1.0.0
     * @param {HTMLInputElement} input Phone input element.
     * @param {Object|null} iti Intl-tel-input instance.
     * @return {string} Normalized phone value.
     */
    function buildPhoneValue(input, iti) {
        var raw = String(input.value || '').trim();
        var digits = digitsOnly(raw);
        var defaultCountry = String(config.defaultCountry || 'br').toLowerCase();

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

    /**
     * Display a feedback message inside the current login scope.
     *
     * @since 1.0.0
     * @param {HTMLElement} scope Login widget root element.
     * @param {string} type Notice type.
     * @param {string} message Message to render.
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
     * Toggle the submit button while an async action is running.
     *
     * @since 1.0.0
     * @param {HTMLFormElement} form Current form.
     * @param {boolean} isLoading Loading state.
     * @param {string} label Loading label.
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
     * Show only the panel that matches the current step.
     *
     * @since 1.0.0
     * @param {HTMLElement} scope Login widget root element.
     * @param {string} step Active step name.
     * @return {void}
     */
    function switchStep(scope, step) {
        scope.querySelectorAll('[data-login-step]').forEach(function (panel) {
            panel.hidden = panel.dataset.loginStep !== step;
        });
    }

    /**
     * Submit a POST request to the plugin AJAX endpoint.
     *
     * @since 1.0.0
     * @param {Object} data Request payload.
     * @return {jQuery.jqXHR} jQuery request handle.
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
     * Format a phone number for display in the UI.
     *
     * @since 1.0.0
     * @param {string} phone Phone number.
     * @return {string} Formatted preview.
     */
    function formatPhonePreview(phone) {
        var raw = String(phone || '').trim();
        var digits = digitsOnly(raw);

        if (!digits) {
            return '';
        }

        if (raw.charAt(0) === '+') {
            return raw;
        }

        return '+' + digits;
    }

    /**
     * Sync the hidden OTP field with the visible one-digit inputs.
     *
     * @since 1.0.0
     * @param {HTMLFormElement} form Current form.
     * @return {string} Combined OTP value.
     */
    function syncOtpHidden(form) {
        var hidden = form.querySelector('[data-otp-hidden]');
        var otp = '';

        form.querySelectorAll('[data-otp-digit]').forEach(function (input) {
            otp += digitsOnly(input.value).slice(0, 1);
        });

        if (hidden) {
            hidden.value = otp;
        }

        return otp;
    }

    /**
     * Focus a specific OTP input.
     *
     * @since 1.0.0
     * @param {HTMLInputElement[]} inputs OTP input list.
     * @param {number} index Input index.
     * @return {void}
     */
    function focusOtpInput(inputs, index) {
        if (inputs[index]) {
            inputs[index].focus();
            inputs[index].select();
        }
    }

    /**
     * Clear all OTP digit inputs and hidden values.
     *
     * @since 1.0.0
     * @param {HTMLFormElement} form Current form.
     * @return {void}
     */
    function clearOtpInputs(form) {
        form.querySelectorAll('[data-otp-digit]').forEach(function (input) {
            input.value = '';
        });

        syncOtpHidden(form);
    }

    /**
     * Update the resend code UI according to the remaining countdown.
     *
     * @since 1.0.0
     * @param {HTMLElement} scope Login widget root element.
     * @param {string} phone Phone number associated with the request.
     * @param {number} secondsLeft Remaining seconds.
     * @return {void}
     */
    function updateResendState(scope, phone, secondsLeft) {
        var container = scope.querySelector('[data-resend-otp]');

        if (!container) {
            return;
        }

        if (secondsLeft > 0) {
            container.innerHTML = '<span class="joinotify-otp-login__resend-label">' + (config.i18n.resendOtpLabel || 'Resend code in') + '</span> <span class="joinotify-otp-login__resend-countdown countdown-otp-resend">' + secondsLeft + '</span> <span>' + (config.i18n.secondsLabel || 'seconds') + '</span>';
            return;
        }

        container.innerHTML = '<button type="button" class="button button-link request-new-otp" data-phone="' + phone + '">' + (config.i18n.resendOtpButton || 'Resend code') + '</button>';
    }

    /**
     * Start or restart the resend countdown for the active login scope.
     *
     * @since 1.0.0
     * @param {HTMLElement} scope Login widget root element.
     * @return {void}
     */
    function startOtpCountdown(scope) {
        var phone = scope.dataset.otpPhone || '';
        var secondsLeft = 60;
        var timerKey = 'otpCountdownTimer';

        if (scope.dataset[timerKey]) {
            window.clearInterval(parseInt(scope.dataset[timerKey], 10));
        }

        updateResendState(scope, phone, secondsLeft);

        var interval = window.setInterval(function () {
            secondsLeft--;
            updateResendState(scope, phone, secondsLeft);

            if (secondsLeft <= 0) {
                window.clearInterval(interval);
                delete scope.dataset[timerKey];
            }
        }, 1000);

        scope.dataset[timerKey] = String(interval);
    }

    /**
     * Verify the OTP code currently entered in the form.
     *
     * @since 1.0.0
     * @param {HTMLFormElement} form Current form.
     * @param {HTMLElement} scope Login widget root element.
     * @return {void}
     */
    function validateOtpCode(form, scope) {
        var phone = form.querySelector('[data-phone-hidden]') ? form.querySelector('[data-phone-hidden]').value : '';
        var otpHidden = form.querySelector('[data-otp-hidden]');
        var otpLength = getOtpLength(scope);
        var otp = otpHidden ? otpHidden.value : syncOtpHidden(form);
        var inputs = Array.prototype.slice.call(form.querySelectorAll('[data-otp-digit]'));
        var firstEmptyIndex = inputs.findIndex(function (input) {
            return !digitsOnly(input.value);
        });

        if (!phone || otp.length !== otpLength) {
            setMessage(scope, 'error', config.i18n.invalidOtp);

            if (firstEmptyIndex >= 0) {
                focusOtpInput(inputs, firstEmptyIndex);
            }

            return;
        }

        if (form.dataset.pendingSubmit === '1') {
            return;
        }

        form.dataset.pendingSubmit = '1';
        setLoading(form, true, config.i18n.verifying);

        ajaxRequest({
            action: 'joinotify_otp_verify_code',
            phone: phone,
            otp: otp,
            remember: form.querySelector('input[name="remember"]') && form.querySelector('input[name="remember"]').checked ? 1 : 0,
            redirect: form.querySelector('input[name="redirect"]').value
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
            delete form.dataset.pendingSubmit;
            setLoading(form, false, config.i18n.verifying);
        });
    }

    /**
     * Bind OTP input navigation and auto-submit behavior.
     *
     * @since 1.0.0
     * @param {HTMLElement} scope Login widget root element.
     * @param {HTMLFormElement} form Current form.
     * @return {void}
     */
    function bindOtpInputs(scope, form) {
        var otpLength = getOtpLength(scope);
        var inputs = Array.prototype.slice.call(form.querySelectorAll('[data-otp-digit]'));

        if (!inputs.length) {
            return;
        }

        form.dataset.otpBound = '1';

        inputs.forEach(function (input, index) {
            if (input.dataset.otpDigitBound === '1') {
                return;
            }

            input.dataset.otpDigitBound = '1';
            input.setAttribute('maxlength', '1');
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('pattern', '[0-9]*');

            input.addEventListener('input', function () {
                var value = digitsOnly(input.value).slice(-1);
                input.value = value;
                syncOtpHidden(form);

                if (value && inputs[index + 1]) {
                    inputs[index + 1].focus();
                }

                if (syncOtpHidden(form).length === otpLength) {
                    validateOtpCode(form, scope);
                }
            });

            input.addEventListener('keydown', function (event) {
                if (event.key === 'Backspace' && !input.value && inputs[index - 1]) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', function (event) {
                var clipboard = event.clipboardData;
                var pastedData = clipboard ? clipboard.getData('text') : '';
                var digits = digitsOnly(pastedData).slice(0, otpLength);

                if (!digits) {
                    return;
                }

                event.preventDefault();

                inputs.forEach(function (field, fieldIndex) {
                    field.value = digits[fieldIndex] || '';
                });

                syncOtpHidden(form);

                if (digits.length === otpLength) {
                    validateOtpCode(form, scope);
                } else {
                    focusOtpInput(inputs, Math.min(digits.length, inputs.length - 1));
                }
            });
        });
    }

    function initIntlTelInputs(context) {
        var telInputs = context.querySelectorAll('[data-phone-visible]');

        telInputs.forEach(function (input) {
            if (input.dataset.intlReady === '1') {
                return;
            }

            var form = input.closest('form');
            var hiddenInput = form ? form.querySelector('[data-phone-hidden]') : null;
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

    function bindPhoneFlow(scope) {
        var phoneForm = scope.querySelector('[data-phone-form]');
        var otpForm = scope.querySelector('[data-otp-form]');
        var passwordForm = scope.querySelector('[data-password-form]');
        var phoneHiddenInputs = scope.querySelectorAll('[data-phone-hidden]');
        var otpPreview = scope.querySelector('[data-otp-phone-preview]');

        if (!phoneForm || !otpForm || !passwordForm) {
            return;
        }

        bindOtpInputs(scope, otpForm);

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

                scope.dataset.otpPhone = response.data.phone || phone;

                if (otpPreview) {
                    otpPreview.textContent = formatPhonePreview(response.data.phone || phone);
                }

                setMessage(scope, response.data.nextStep === 'password' ? 'info' : 'success', response.data.message);

                if (response.data.nextStep === 'otp') {
                    switchStep(scope, 'otp');
                    clearOtpInputs(otpForm);
                    startOtpCountdown(scope);

                    var firstOtpInput = otpForm.querySelector('[data-otp-digit]');

                    if (firstOtpInput) {
                        firstOtpInput.focus();
                    }
                } else if (response.data.nextStep === 'password') {
                    switchStep(scope, 'password');
                    var emailInput = passwordForm.querySelector('input[name="email"]');

                    if (emailInput) {
                        emailInput.focus();
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
            validateOtpCode(otpForm, scope);
        });

        passwordForm.addEventListener('submit', function (event) {
            event.preventDefault();

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
                switchStep(scope, button.dataset.switchStep);
                setMessage(scope, 'info', '');

                if (button.dataset.switchStep === 'phone') {
                    clearOtpInputs(otpForm);
                }
            });
        });

    }

    function bindRegisterForms(context) {
        context.querySelectorAll('.joinotify-otp-register-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
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

    document.addEventListener('DOMContentLoaded', function () {
        initIntlTelInputs(document);

        document.addEventListener('click', function (event) {
            var button = event.target.closest('.request-new-otp');

            if (!button) {
                return;
            }

            event.preventDefault();

            var scope = button.closest('[data-joinotify-otp-login]');
            var phoneForm = scope ? scope.querySelector('[data-phone-form]') : null;

            if (phoneForm) {
                phoneForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            }
        });

        document.querySelectorAll('[data-joinotify-otp-login]').forEach(function (scope) {
            bindPhoneFlow(scope);
        });

        bindRegisterForms(document);
    });
})(jQuery);

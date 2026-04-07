(function ($) {
    'use strict';

    var config = window.joinotifyOtpLogin || {};

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
                var value = input.value;

                if (iti && typeof iti.getNumber === 'function') {
                    value = iti.getNumber() || input.value;
                }

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

    function setMessage(scope, type, message) {
        var notice = scope.querySelector('[data-login-notice]');

        if (!notice) {
            return;
        }

        notice.className = 'joinotify-otp-notice joinotify-otp-notice--' + type;
        notice.textContent = message || '';
        notice.hidden = !message;
    }

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

    function switchStep(scope, step) {
        scope.querySelectorAll('[data-login-step]').forEach(function (panel) {
            panel.hidden = panel.dataset.loginStep !== step;
        });
    }

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

            var phone = phoneForm.querySelector('[data-phone-hidden]').value;

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

        document.querySelectorAll('.joinotify-otp-login').forEach(function (scope) {
            bindPhoneFlow(scope);
        });

        bindRegisterForms(document);
    });
})(jQuery);

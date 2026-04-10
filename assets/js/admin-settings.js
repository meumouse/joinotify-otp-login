/**
 * Joinotify OTP Login admin settings behavior.
 *
 * Handles the settings popup, primary color synchronization, live palette
 * regeneration, and border radius preview updates without additional CSS.
 */
(function ($) {
	'use strict';

	/**
	 * Normalize a hex color string to lowercase #rrggbb form.
	 *
	 * @param {string} value Raw color input.
	 * @return {string} Normalized hex color or empty string.
	 */
	var normalizeHex = function (value) {
		var hex = String(value || '').trim().replace(/^#/, '');

		if (!/^[0-9a-fA-F]{6}$/.test(hex)) {
			return '';
		}

		return '#' + hex.toLowerCase();
	};

	/**
	 * Convert a normalized hex color to an RGB tuple.
	 *
	 * @param {string} hex Normalized hex color.
	 * @return {number[]} RGB components.
	 */
	var hexToRgb = function (hex) {
		var value = normalizeHex(hex).replace('#', '');

		if (value.length !== 6) {
			return [79, 70, 229];
		}

		return [
			parseInt(value.slice(0, 2), 16),
			parseInt(value.slice(2, 4), 16),
			parseInt(value.slice(4, 6), 16)
		];
	};

	/**
	 * Calculate relative luminance for an RGB color.
	 *
	 * @param {number} r Red channel.
	 * @param {number} g Green channel.
	 * @param {number} b Blue channel.
	 * @return {number} Relative luminance.
	 */
	var luminance = function (r, g, b) {
		var channels = [r, g, b].map(function (channel) {
			var value = channel / 255;
			return value <= 0.03928 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
		});

		return (channels[0] * 0.2126) + (channels[1] * 0.7152) + (channels[2] * 0.0722);
	};

	/**
	 * Interpolate between two RGB colors.
	 *
	 * @param {number[]} color1 Starting color.
	 * @param {number[]} color2 Ending color.
	 * @param {number} factor Blend factor between 0 and 1.
	 * @return {number[]} Interpolated color.
	 */
	var interpolateColor = function (color1, color2, factor) {
		var safeFactor = Math.max(0, Math.min(1, parseFloat(factor) || 0));
		var result = [];

		for (var i = 0; i < color1.length; i++) {
			result.push(Math.round(color1[i] + ((color2[i] - color1[i]) * safeFactor)));
		}

		return result;
	};

	/**
	 * Convert an RGB tuple to a hex color string.
	 *
	 * @param {number[]} color RGB components.
	 * @return {string} Hex color string.
	 */
	var rgbToHex = function (color) {
		var channels = color.map(function (value) {
			return Math.max(0, Math.min(255, parseInt(value, 10) || 0));
		});

		return '#' + channels.map(function (value) {
			return value.toString(16).padStart(2, '0');
		}).join('');
	};

	/**
	 * Generate the palette shades from a base color.
	 *
	 * @param {string} hex Base hex color.
	 * @return {Object<string,string>} Palette indexed by shade step.
	 */
	var generatePalette = function (hex) {
		var inputColor = hexToRgb(hex);
		var inputLuminance = luminance(inputColor[0], inputColor[1], inputColor[2]);
		var lightestColor = [245, 245, 245];
		var darkestColor = [8, 8, 8];
		var lightestLuminance = luminance(245, 245, 245);
		var darkestLuminance = luminance(8, 8, 8);
		var luminanceRange = lightestLuminance - darkestLuminance;
		var rows = {
			'0': '#ffffff'
		};
		var steps = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

		steps.forEach(function (step) {
			var targetLuminance = lightestLuminance - ((step / 1000) * luminanceRange);
			var resultColor;

			if (targetLuminance > inputLuminance) {
				var factor = (targetLuminance - inputLuminance) / Math.max(lightestLuminance - inputLuminance, 0.0001);
				resultColor = interpolateColor(inputColor, lightestColor, factor);
			} else {
				var darkFactor = (inputLuminance - targetLuminance) / Math.max(inputLuminance - darkestLuminance, 0.0001);
				resultColor = interpolateColor(inputColor, darkestColor, darkFactor);
			}

			rows[String(step)] = rgbToHex(resultColor);
		});

		return rows;
	};

	/**
	 * Bind the settings popup and live field synchronization handlers.
	 */
	$(function () {
		var trigger = $('#joinotify_otp_login_settings_trigger');
		var container = $('#joinotify-otp-login-settings-modal');

		if (!trigger.length || !container.length) {
			return;
		}

		var close = $('#joinotify_otp_login_settings_close');
		var primaryText = $('#otp_login_primary_color');
		var primaryPicker = container.find('[data-joinotify-otp-login-primary-picker]');
		var radiusValue = $('#otp_login_border_radius_value');
		var radiusUnit = $('#otp_login_border_radius_unit');
		var radiusOutput = $('#otp_login_border_radius_output');
		var paletteRows = container.find('[data-joinotify-otp-login-palette-row]');

		var closeModal = function () {
			container.removeClass('show');
		};

		var syncRadius = function () {
			var value = String(radiusValue.val() || '0.375').trim() || '0.375';
			var unit = String(radiusUnit.val() || 'rem').trim() || 'rem';

			radiusOutput.text(value + unit);
		};

		var syncPalette = function (baseColor) {
			var normalized = normalizeHex(baseColor);

			if (!normalized) {
				return;
			}

			var palette = generatePalette(normalized);

			paletteRows.each(function () {
				var row = $(this);
				var step = String(row.data('step'));
				var color = palette[step] || '#ffffff';

				row.find('[data-joinotify-otp-login-palette-picker]').val(color);
				row.find('[data-joinotify-otp-login-palette-text]').val(color);
			});
		};

		var syncPrimaryColor = function (value, source) {
			var normalized = normalizeHex(value);

			if (!normalized) {
				return;
			}

			if ('text' !== source) {
				primaryText.val(normalized);
			}

			if ('picker' !== source && primaryPicker.length) {
				primaryPicker.val(normalized);
			}

			syncPalette(normalized);
		};

		trigger.on('click', function (e) {
			e.preventDefault();
			container.addClass('show');
		});

		container.on('click', function (e) {
			if (e.target === this) {
				closeModal();
			}
		});

		close.on('click', function (e) {
			e.preventDefault();
			closeModal();
		});

		$(document).on('keydown', function (e) {
			if ('Escape' === e.key && container.hasClass('show')) {
				closeModal();
			}
		});

		primaryText.on('input change', function () {
			syncPrimaryColor($(this).val(), 'text');
		});

		primaryPicker.on('input change', function () {
			syncPrimaryColor($(this).val(), 'picker');
		});

		radiusValue.on('input change', syncRadius);
		radiusUnit.on('change', syncRadius);

		syncPrimaryColor(primaryText.val(), 'text');
		syncRadius();
	});
})(jQuery);

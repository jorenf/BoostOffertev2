/**
 * Offerte Frontend JavaScript.
 *
 * Handles: signature pad initialization, terms checkbox, acceptance flow.
 *
 * @package Bossier_Calculator_Builder
 */

/* global jQuery, bsOfferte, SignaturePad */

(function ($) {
    'use strict';

    var BSOfferteFrontend = {
        signaturePad: null,

        init: function () {
            this.initSignaturePad();
            this.bindEvents();
        },

        initSignaturePad: function () {
            var canvas = document.getElementById('bs-signature-pad');
            if (!canvas) return;

            this.signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)'
            });

            // Handle responsive resize.
            this.resizeCanvas(canvas);
            $(window).on('resize', function () {
                BSOfferteFrontend.resizeCanvas(canvas);
            });
        },

        resizeCanvas: function (canvas) {
            if (!canvas || !this.signaturePad) return;

            var wrapper = canvas.parentElement;
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            var width = Math.min(wrapper.offsetWidth, 500);
            var height = 200;

            // Save current data.
            var data = this.signaturePad.toData();

            canvas.width = width * ratio;
            canvas.height = height * ratio;
            canvas.style.width = width + 'px';
            canvas.style.height = height + 'px';
            canvas.getContext('2d').scale(ratio, ratio);

            // Restore data.
            this.signaturePad.clear();
            if (data.length) {
                this.signaturePad.fromData(data);
            }
        },

        bindEvents: function () {
            // Clear signature button.
            $('#bs-clear-signature').on('click', function () {
                if (BSOfferteFrontend.signaturePad) {
                    BSOfferteFrontend.signaturePad.clear();
                }
                BSOfferteFrontend.updateAcceptButton();
            });

            // Terms checkbox.
            $('#bs-accept-terms').on('change', function () {
                BSOfferteFrontend.updateAcceptButton();
            });

            // Monitor signature pad changes.
            var canvas = document.getElementById('bs-signature-pad');
            if (canvas) {
                canvas.addEventListener('pointerup', function () {
                    BSOfferteFrontend.updateAcceptButton();
                });
            }

            // Accept button.
            $('#bs-accept-offerte').on('click', this.acceptOfferte.bind(this));
        },

        updateAcceptButton: function () {
            var termsChecked = $('#bs-accept-terms').is(':checked');
            var hasSig = this.signaturePad && !this.signaturePad.isEmpty();
            $('#bs-accept-offerte').prop('disabled', !(termsChecked && hasSig));
        },

        acceptOfferte: function () {
            var self = this;

            // Validate signature.
            if (!this.signaturePad || this.signaturePad.isEmpty()) {
                this.showMessage(bsOfferte.i18n.signatureRequired, 'error');
                return;
            }

            // Validate terms.
            if (!$('#bs-accept-terms').is(':checked')) {
                this.showMessage(bsOfferte.i18n.termsRequired, 'error');
                return;
            }

            var $btn = $('#bs-accept-offerte');
            $btn.prop('disabled', true).text(bsOfferte.i18n.processing);
            this.hideMessage();

            var signatureData = this.signaturePad.toDataURL('image/png');

            $.post(bsOfferte.ajaxUrl, {
                action: 'bs_accept_offerte',
                nonce: bsOfferte.nonce,
                offerte_id: bsOfferte.offerteId,
                token: bsOfferte.token,
                signature: signatureData
            }, function (response) {
                if (response.success) {
                    // Redirect to accepted page.
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    self.showMessage(response.data.message || bsOfferte.i18n.error, 'error');
                    $btn.prop('disabled', false).text('Offerte Accepteren');
                }
            }).fail(function () {
                self.showMessage(bsOfferte.i18n.error, 'error');
                $btn.prop('disabled', false).text('Offerte Accepteren');
            });
        },

        showMessage: function (msg, type) {
            var $el = $('#bs-accept-message');
            $el.removeClass('bs-msg-error bs-msg-success')
               .addClass(type === 'error' ? 'bs-msg-error' : 'bs-msg-success')
               .text(msg)
               .show();
        },

        hideMessage: function () {
            $('#bs-accept-message').hide();
        }
    };

    $(document).ready(function () {
        if ($('#bs-accept-section').length || $('#bs-signature-pad').length) {
            BSOfferteFrontend.init();
        }
    });

})(jQuery);

/**
 * Offerte Admin JavaScript.
 *
 * Handles: line items, customer search, live pricing, totals recalculation, action buttons.
 *
 * @package Bossier_Calculator_Builder
 */

/* global jQuery, bsOfferteAdmin */

(function ($) {
    'use strict';

    var BSOfferte = {
        itemIndex: 0,
        searchTimer: null,

        init: function () {
            this.itemIndex = $('#bs-items-body .bs-item-row').length;
            this.bindEvents();
            this.recalcTotals();
        },

        bindEvents: function () {
            // Line items.
            $('#bs-add-item').on('click', this.addItem.bind(this));
            $(document).on('click', '.bs-remove-item', this.removeItem.bind(this));
            $(document).on('change keyup', '.bs-item-qty, .bs-item-unit-price', this.updateLineTotal.bind(this));
            $('#bs-shipping-cost').on('change keyup', this.recalcTotals.bind(this));

            // Customer search.
            $('#bs-customer-search').on('keyup', this.debounceSearch.bind(this));
            $(document).on('click', '.bs-customer-result-item', this.selectCustomer.bind(this));
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#bs-customer-search, #bs-customer-results').length) {
                    $('#bs-customer-results').hide();
                }
            });

            // Actions.
            $('#bs-save-draft').on('click', function () { BSOfferte.saveOfferte('draft'); });
            $('#bs-send-offerte').on('click', this.sendOfferte.bind(this));
            $('#bs-generate-pdf').on('click', this.generatePDF.bind(this));
            $('#bs-cancel-offerte').on('click', this.cancelOfferte.bind(this));
            $('#bs-duplicate-offerte').on('click', this.duplicateOfferte.bind(this));
        },

        // ── Line Items ──

        addItem: function () {
            var template = $('#bs-item-row-template').html();
            var html = template.replace(/\{\{index\}\}/g, this.itemIndex);
            $('#bs-items-body').append(html);
            this.itemIndex++;
        },

        removeItem: function (e) {
            $(e.currentTarget).closest('.bs-item-row').remove();
            this.recalcTotals();
        },

        updateLineTotal: function (e) {
            var $row = $(e.currentTarget).closest('.bs-item-row');
            this.calcRowTotal($row);
            this.recalcTotals();
        },

        calcRowTotal: function ($row) {
            var qty = parseInt($row.find('.bs-item-qty').val(), 10) || 0;
            var price = parseFloat($row.find('.bs-item-unit-price').val()) || 0;
            var total = qty * price;

            $row.find('.bs-item-line-total').text(this.formatNumber(total));
            $row.find('.bs-item-line-total-input').val(total.toFixed(2));
        },

        recalcTotals: function () {
            var subtotal = 0;

            $('#bs-items-body .bs-item-row').each(function () {
                var val = parseFloat($(this).find('.bs-item-line-total-input').val()) || 0;
                subtotal += val;
            });

            var shipping = parseFloat($('#bs-shipping-cost').val()) || 0;
            var taxable = subtotal + shipping;
            var tax = taxable * 0.21;
            var total = taxable + tax;

            $('#bs-subtotal').text(this.formatNumber(subtotal));
            $('#bs-shipping-display').text(this.formatNumber(shipping));
            $('#bs-tax').text(this.formatNumber(tax));
            $('#bs-total').html('<strong>' + this.formatNumber(total) + '</strong>');
        },

        // ── Customer Search ──

        debounceSearch: function () {
            var self = this;
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(function () {
                self.searchCustomers();
            }, 300);
        },

        searchCustomers: function () {
            var term = $.trim($('#bs-customer-search').val());
            if (term.length < 2) {
                $('#bs-customer-results').hide();
                return;
            }

            $.post(bsOfferteAdmin.ajaxUrl, {
                action: 'bs_search_customers',
                nonce: bsOfferteAdmin.nonce,
                term: term
            }, function (response) {
                if (response.success && response.data.length) {
                    var html = '';
                    $.each(response.data, function (i, c) {
                        html += '<div class="bs-customer-result-item" data-customer=\'' + JSON.stringify(c) + '\'>';
                        html += '<strong>' + BSOfferte.escHtml(c.name) + '</strong>';
                        if (c.company) html += ' – ' + BSOfferte.escHtml(c.company);
                        html += '<br><small>' + BSOfferte.escHtml(c.email) + '</small>';
                        html += '</div>';
                    });
                    $('#bs-customer-results').html(html).show();
                } else {
                    $('#bs-customer-results').hide();
                }
            });
        },

        selectCustomer: function (e) {
            var data = $(e.currentTarget).data('customer');
            if (!data) return;

            $('input[name="customer[naam]"]').val(data.name || '');
            $('input[name="customer[bedrijf]"]').val(data.company || '');
            $('input[name="customer[email]"]').val(data.email || '');
            $('input[name="customer[tel]"]').val(data.phone || '');

            if (data.address) {
                $('input[name="customer[straat]"]').val(data.address.street || '');
                $('input[name="customer[postcode]"]').val(data.address.postcode || '');
                $('input[name="customer[plaats]"]').val(data.address.city || '');
                $('select[name="customer[land]"]').val(data.address.country || 'NL');
            }

            $('#bs-customer-search').val('');
            $('#bs-customer-results').hide();
        },

        // ── Actions ──

        collectData: function () {
            var data = {
                nonce: bsOfferteAdmin.nonce,
                offerte_id: $('#bs-offerte-id').val() || 0,
                customer: {},
                items: [],
                shipping_cost: $('#bs-shipping-cost').val(),
                valid_until: $('input[name="valid_until"]').val(),
                payment_method: $('select[name="payment_method"]').val(),
                reminder_days: $('select[name="reminder_days"]').val(),
                customer_note: $('textarea[name="customer_note"]').val()
            };

            // Customer.
            data.customer = {
                bedrijf: $('input[name="customer[bedrijf]"]').val(),
                naam: $('input[name="customer[naam]"]').val(),
                email: $('input[name="customer[email]"]').val(),
                tel: $('input[name="customer[tel]"]').val(),
                straat: $('input[name="customer[straat]"]').val(),
                postcode: $('input[name="customer[postcode]"]').val(),
                plaats: $('input[name="customer[plaats]"]').val(),
                land: $('select[name="customer[land]"]').val()
            };

            // Items.
            $('#bs-items-body .bs-item-row').each(function () {
                var $row = $(this);
                data.items.push({
                    category: $row.find('.bs-item-category').val(),
                    title: $row.find('.bs-item-title').val(),
                    product_id: $row.find('.bs-item-product-id').val(),
                    calculator_id: $row.find('.bs-item-calc-id').val(),
                    lengte: $row.find('input[name*="[lengte]"]').val(),
                    breedte: $row.find('input[name*="[breedte]"]').val(),
                    hoogte: $row.find('input[name*="[hoogte]"]').val(),
                    kleur: $row.find('input[name*="[kleur]"]').val(),
                    afwerking: $row.find('input[name*="[afwerking]"]').val(),
                    quantity: $row.find('.bs-item-qty').val(),
                    unit_price: $row.find('.bs-item-unit-price').val(),
                    line_total: $row.find('.bs-item-line-total-input').val()
                });
            });

            return data;
        },

        saveOfferte: function (mode) {
            var data = this.collectData();
            data.action = 'bs_save_offerte';

            var $btn = mode === 'draft' ? $('#bs-save-draft') : $('#bs-send-offerte');
            $btn.prop('disabled', true).text(bsOfferteAdmin.i18n.saving);

            $.post(bsOfferteAdmin.ajaxUrl, data, function (response) {
                if (response.success) {
                    $btn.text(bsOfferteAdmin.i18n.saved);

                    // If new offerte, redirect to edit page.
                    if (!$('#bs-offerte-id').val() && response.data.offerte_id) {
                        window.location.href = bsOfferteAdmin.editUrl + response.data.offerte_id;
                        return;
                    }

                    $('#bs-offerte-id').val(response.data.offerte_id);

                    setTimeout(function () {
                        $btn.prop('disabled', false).text(
                            mode === 'draft' ? 'Opslaan als Concept' : 'Offerte Verzenden'
                        );
                    }, 1500);
                } else {
                    alert(response.data.message || bsOfferteAdmin.i18n.error);
                    $btn.prop('disabled', false).text(
                        mode === 'draft' ? 'Opslaan als Concept' : 'Offerte Verzenden'
                    );
                }
            }).fail(function () {
                alert(bsOfferteAdmin.i18n.error);
                $btn.prop('disabled', false);
            });
        },

        sendOfferte: function () {
            // Validate email.
            var email = $('input[name="customer[email]"]').val();
            if (!email) {
                alert(bsOfferteAdmin.i18n.noCustomerEmail);
                return;
            }

            if (!confirm(bsOfferteAdmin.i18n.confirmSend)) {
                return;
            }

            var data = this.collectData();
            data.action = 'bs_send_offerte';

            var $btn = $('#bs-send-offerte');
            $btn.prop('disabled', true).text(bsOfferteAdmin.i18n.sending);

            $.post(bsOfferteAdmin.ajaxUrl, data, function (response) {
                if (response.success) {
                    $btn.text(bsOfferteAdmin.i18n.sent);

                    // Redirect to edit to refresh status.
                    if (response.data.offerte_id) {
                        window.location.href = bsOfferteAdmin.editUrl + response.data.offerte_id;
                    }
                } else {
                    alert(response.data.message || bsOfferteAdmin.i18n.error);
                    $btn.prop('disabled', false).text('Offerte Verzenden');
                }
            }).fail(function () {
                alert(bsOfferteAdmin.i18n.error);
                $btn.prop('disabled', false);
            });
        },

        generatePDF: function () {
            var offerteId = $('#bs-offerte-id').val();
            if (!offerteId) {
                alert('Sla de offerte eerst op.');
                return;
            }

            var $btn = $('#bs-generate-pdf');
            $btn.prop('disabled', true).text('Genereren...');

            $.post(bsOfferteAdmin.ajaxUrl, {
                action: 'bs_generate_offerte_pdf',
                nonce: bsOfferteAdmin.nonce,
                offerte_id: offerteId
            }, function (response) {
                if (response.success && response.data.url) {
                    window.open(response.data.url, '_blank');
                } else {
                    alert(response.data.message || bsOfferteAdmin.i18n.error);
                }
                $btn.prop('disabled', false).text('PDF Genereren');
            }).fail(function () {
                alert(bsOfferteAdmin.i18n.error);
                $btn.prop('disabled', false).text('PDF Genereren');
            });
        },

        cancelOfferte: function () {
            if (!confirm(bsOfferteAdmin.i18n.confirmCancel)) return;

            var offerteId = $('#bs-offerte-id').val();
            if (!offerteId) return;

            $.post(bsOfferteAdmin.ajaxUrl, {
                action: 'bs_cancel_offerte',
                nonce: bsOfferteAdmin.nonce,
                offerte_id: offerteId
            }, function (response) {
                if (response.success) {
                    window.location.href = bsOfferteAdmin.editUrl + offerteId;
                } else {
                    alert(response.data.message || bsOfferteAdmin.i18n.error);
                }
            });
        },

        duplicateOfferte: function () {
            if (!confirm(bsOfferteAdmin.i18n.confirmDuplicate)) return;

            var offerteId = $('#bs-offerte-id').val();
            if (!offerteId) return;

            $.post(bsOfferteAdmin.ajaxUrl, {
                action: 'bs_duplicate_offerte',
                nonce: bsOfferteAdmin.nonce,
                offerte_id: offerteId
            }, function (response) {
                if (response.success && response.data.offerte_id) {
                    window.location.href = bsOfferteAdmin.editUrl + response.data.offerte_id;
                } else {
                    alert(response.data.message || bsOfferteAdmin.i18n.error);
                }
            });
        },

        // ── Helpers ──

        formatNumber: function (num) {
            return num.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },

        escHtml: function (str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }
    };

    $(document).ready(function () {
        if ($('#bs-items-table').length) {
            BSOfferte.init();
        }
    });

})(jQuery);

// ==========================================================================

// Gift Voucher Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

// @codekit-prepend '_voucher-index.js'    
// @codekit-prepend '_code-index.js'    

if (typeof Craft.GiftVoucher === typeof undefined) {
    Craft.GiftVoucher = {};
}

(function($) {

Craft.GiftVoucher.CpAddVoucher = Garnish.Base.extend({
    orderNumber: null,

    init: function(orderNumber) {
        this.orderNumber = orderNumber;

        // Find the settings menubtn, and add a new option to it
        var $menubtn = $('.menubtn[data-icon="settings"]').data('menubtn');

        if ($menubtn) {
            var $newOption = $('<li><a data-action="gift-vouchers">' + Craft.t('gift-voucher', 'Manage gift vouchers') + '</a></li>');

            // Add the option to the menubtn
            $menubtn.menu.addOptions($newOption.children());

            // Add it to the DOM
            $newOption.prependTo($menubtn.menu.$container.children().first());

            // Hijack the event
            $menubtn.menu.on('optionselect', $.proxy(this, '_handleMenuBtn'));
        }
    },

    _handleMenuBtn: function(ev) {
        var $option = $(ev.selectedOption);

        // Just action our option
        if ($option.data('action') == 'gift-vouchers') {
            new Craft.GiftVoucher.GiftVouchersModal();
        }
    },
});

Craft.GiftVoucher.GiftVouchersModal = Garnish.Modal.extend({
    init: function() {
        this.$form = $('<form class="modal fitted gift-voucher-modal" method="post" accept-charset="UTF-8"/>').appendTo(Garnish.$bod);
        this.$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo(this.$form);

        var $footer = $('<div class="footer"/>').appendTo(this.$form);
        var $mainBtnGroup = $('<div class="buttons right"/>').appendTo($footer);
        this.$cancelBtn = $('<input type="button" class="btn cancel" value="' + Craft.t('gift-voucher', 'Cancel') + '"/>').appendTo($mainBtnGroup);
        this.$saveBtn = $('<input type="submit" class="btn submit" value="' + Craft.t('gift-voucher', 'Add voucher code') + '"/>').appendTo($mainBtnGroup);
        this.$footerSpinner = $('<div class="spinner right hidden"/>').appendTo($footer);

        Craft.initUiElements(this.$form);

        this.addListener(this.$cancelBtn, 'click', 'onFadeOut');
        this.addListener(this.$saveBtn, 'click', 'onSubmit');

        this.base(this.$form);

        var data = {
            orderId: this.getOrderId(),
        };

        Craft.sendActionRequest('POST', 'gift-voucher/vouchers/get-modal-body', { data })
            .then((response) => {
                this.$body.html(response.data.html);
            });

        $(this.$form).on('click', '[data-code]', $.proxy(this.removeCode, this));
    },

    onFadeOut() {
        this.$form.remove();
        this.$shade.remove();
    },

    getOrderId() {
        // Fetch Order ID from URL
        var pathArray = window.location.pathname.split('/');

        return pathArray[pathArray.length - 1];
    },

    removeCode: function(e) {
        e.preventDefault();

        var data = {
            voucherCode: e.currentTarget.getAttribute('data-code'),
            orderId: this.getOrderId(),
        };

        this.$footerSpinner.removeClass('hidden');

        Craft.sendActionRequest('POST', 'gift-voucher/cart/remove-code', { data })
            .then((response) => {
                if (response.data.success) {
                    Craft.cp.displayNotice(Craft.t('gift-voucher', 'Voucher code removed.'));

                    this.onFadeOut();
                } else {
                    Craft.cp.displayError(response.data.error);
                    
                    this.$footerSpinner.addClass('hidden');
                }
            });
    },

    onSubmit: function(e) {
        e.preventDefault();

        this.$footerSpinner.removeClass('hidden');

        var data = this.$form.serialize();

        // Save everything through the normal update-cart action, just like we were doing it on the front-end
        Craft.sendActionRequest('POST', 'gift-voucher/cart/add-code', { data })
            .then((response) => {
                this.$footerSpinner.addClass('hidden');

                if (response.data.success) {
                    Craft.cp.displayNotice(Craft.t('gift-voucher', 'Voucher code applied.'));

                    this.onFadeOut();
                } else {
                    Craft.cp.displayError(response.data.error);
                    
                    this.$footerSpinner.addClass('hidden');
                }
            });
    },
});

})(jQuery);

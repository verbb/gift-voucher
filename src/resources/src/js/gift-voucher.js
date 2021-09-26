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
            var $newOption = $('<li><a data-action="apply-gift-voucher">' + Craft.t('gift-voucher', 'Add voucher code') + '</a></li>');

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
        if ($option.data('action') == 'apply-gift-voucher') {
            new Craft.GiftVoucher.AddCodeModal();
        }
    },
});

Craft.GiftVoucher.AddCodeModal = Garnish.Modal.extend({
    init: function() {
        this.$form = $('<form class="modal fitted add-code-modal" method="post" accept-charset="UTF-8"/>').appendTo(Garnish.$bod);
        this.$body = $('<div class="pane"></div>').appendTo(this.$form);

        Craft.ui.createTextField({
            label: Craft.t('gift-voucher', 'Voucher Code'),
            instructions: Craft.t('gift-voucher', 'Enter the gift voucher code to be applied on the order.'),
            id: 'voucher-code',
            name: 'voucherCode'
        }).appendTo(this.$body);

        // Fetch Order ID from URL
        var pathArray = window.location.pathname.split('/');
        var orderId = pathArray[pathArray.length - 1];

        $('<input type="hidden" name="orderId" value="' + orderId + '">').appendTo(this.$body);

        var $footer = $('<div class="footer"/>').appendTo(this.$body);
        var $mainBtnGroup = $('<div class="buttons right"/>').appendTo($footer);
        this.$cancelBtn = $('<input type="button" class="btn" value="' + Craft.t('commerce', 'Cancel') + '"/>').appendTo($mainBtnGroup);
        this.$updateBtn = $('<input type="button" class="btn submit" value="' + Craft.t('gift-voucher', 'Add voucher code') + '"/>').appendTo($mainBtnGroup);
        this.$footerSpinner = $('<div class="spinner right hidden"/>').appendTo($footer);

        Craft.initUiElements(this.$form);

        this.addListener(this.$cancelBtn, 'click', 'onFadeOut');
        this.addListener(this.$updateBtn, 'click', 'onSubmit');

        this.base(this.$form, settings);
    },

    onFadeOut: function() {
        this.$form.remove();
        this.$shade.remove();
    },

    onSubmit: function(e) {
        e.preventDefault();

        this.$footerSpinner.removeClass('hidden');

        var data = this.$form.serialize();

        // Save everything through the normal update-cart action, just like we were doing it on the front-end
        Craft.postActionRequest('gift-voucher/cart/add-code', data, $.proxy(function(response) {
            this.$footerSpinner.addClass('hidden');

            if (response.success) {
                location.reload();
            } else {
                Craft.cp.displayError(response.error);
            }
        }, this));
    },
});

})(jQuery);

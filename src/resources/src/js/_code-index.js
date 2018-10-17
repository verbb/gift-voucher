(function($){

if (typeof Craft.GiftVoucher === 'undefined') {
    Craft.GiftVoucher = {};
}

var elementTypeClass = 'verbb\\giftvoucher\\elements\\Code';

/**
 * Product index class
 */
Craft.GiftVoucher.LicenseIndex = Craft.BaseElementIndex.extend({

    afterInit: function() {
        var href = 'href="' + Craft.getUrl('gift-voucher/codes/new') + '"',
            label = Craft.t('gift-voucher', 'New code');

        this.$newProductBtnGroup = $('<div class="btngroup submit"/>');
        this.$newProductBtn = $('<a class="btn submit add icon" ' + href + '>' + label + '</a>').appendTo(this.$newProductBtnGroup);

        this.addButton(this.$newProductBtnGroup);

        this.base();
    }
});

// Register it!
try {
    Craft.registerElementIndexClass(elementTypeClass, Craft.GiftVoucher.LicenseIndex);
}
catch(e) {
    // Already registered
}

})(jQuery);

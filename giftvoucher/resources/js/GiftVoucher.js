(function($){

    // ----------------------------------------------------------
    // Show/hide voucher price field when open amount is clicked
    // ----------------------------------------------------------

    $(document).on('change', '#customAmount', function() {
        var $priceField = $('#price-field');

        if ($(this).hasClass('on')) {
            $priceField.hide();
        } else {
            $priceField.show();
        }
    });

})(jQuery);

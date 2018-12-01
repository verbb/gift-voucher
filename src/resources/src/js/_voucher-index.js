(function($){

if (typeof Craft.GiftVoucher === 'undefined') {
    Craft.GiftVoucher = {};
}

var elementTypeClass = 'verbb\\giftvoucher\\elements\\Voucher';

Craft.GiftVoucher.VoucherIndex = Craft.BaseElementIndex.extend({
    editableVoucherTypes: null,
    $newVoucherBtnVoucherType: null,
    $newVoucherBtn: null,

    init: function(elementType, $container, settings) {
        this.on('selectSource', $.proxy(this, 'updateButton'));
        this.on('selectSite', $.proxy(this, 'updateButton'));
        this.base(elementType, $container, settings);
    },

    afterInit: function() {
        // Find which of the visible voucherTypes the user has permission to create new vouchers in
        this.editableVoucherTypes = [];

        for (var i = 0; i < Craft.GiftVoucher.editableVoucherTypes.length; i++) {
            var voucherType = Craft.GiftVoucher.editableVoucherTypes[i];

            if (this.getSourceByKey('voucherType:' + voucherType.id)) {
                this.editableVoucherTypes.push(voucherType);
            }
        }

        this.base();
    },

    getDefaultSourceKey: function() {
        // Did they request a specific voucher type in the URL?
        if (this.settings.context === 'index' && typeof defaultVoucherTypeHandle !== 'undefined') {
            for (var i = 0; i < this.$sources.length; i++) {
                var $source = $(this.$sources[i]);
                
                if ($source.data('handle') === defaultVoucherTypeHandle) {
                    return $source.data('key');
                }
            }
        }

        return this.base();
    },

    updateButton: function() {
        if (!this.$source) {
            return;
        }

        // Get the handle of the selected source
        var selectedSourceHandle = this.$source.data('handle');

        // Update the New Voucher button
        // ---------------------------------------------------------------------

        if (this.editableVoucherTypes.length) {
            // Remove the old button, if there is one
            if (this.$newVoucherBtnVoucherType) {
                this.$newVoucherBtnVoucherType.remove();
            }

            // Determine if they are viewing a voucherType that they have permission to create vouchers in
            var selectedVoucherType;

            if (selectedSourceHandle) {
                for (i = 0; i < this.editableVoucherTypes.length; i++) {
                    if (this.editableVoucherTypes[i].handle === selectedSourceHandle) {
                        selectedVoucherType = this.editableVoucherTypes[i];
                        break;
                    }
                }
            }

            this.$newVoucherBtnGroup = $('<div class="btngroup submit"/>');
            var $menuBtn;

            // If they are, show a primary "New voucher" button, and a dropdown of the other voucher types (if any).
            // Otherwise only show a menu button
            if (selectedVoucherType) {
                var href = this._getVoucherTypeTriggerHref(selectedVoucherType),
                    label = (this.settings.context === 'index' ? Craft.t('gift-voucher', 'New voucher') : Craft.t('gift-voucher', 'New {voucherType} voucher', { voucherType: selectedVoucherType.name }));
                this.$newVoucherBtn = $('<a class="btn submit add icon" ' + href + '>' + label + '</a>').appendTo(this.$newVoucherBtnGroup);

                if (this.settings.context !== 'index') {
                    this.addListener(this.$newVoucherBtn, 'click', function(ev) {
                        this._openCreateVoucherModal(ev.currentTarget.getAttribute('data-id'));
                    });
                }

                if (this.editableVoucherTypes.length > 1) {
                    $menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newVoucherBtnGroup);
                }
            } else {
                this.$newVoucherBtn = $menuBtn = $('<div class="btn submit add icon menubtn">'+Craft.t('gift-voucher', 'New voucher')+'</div>').appendTo(this.$newVoucherBtnGroup);
            }

            if ($menuBtn) {
                var menuHtml = '<div class="menu"><ul>';

                for (var i = 0; i < this.editableVoucherTypes.length; i++) {
                    var voucherType = this.editableVoucherTypes[i];

                    if (this.settings.context === 'index' || voucherType !== selectedVoucherType) {
                        var href = this._getVoucherTypeTriggerHref(voucherType),
                            label = (this.settings.context === 'index' ? voucherType.name : Craft.t('gift-voucher', 'New {voucherType} voucher', {voucherType: voucherType.name}));
                        menuHtml += '<li><a '+href+'">'+label+'</a></li>';
                    }
                }

                menuHtml += '</ul></div>';

                $(menuHtml).appendTo(this.$newVoucherBtnGroup);
                var menuBtn = new Garnish.MenuBtn($menuBtn);

                if (this.settings.context !== 'index') {
                    menuBtn.on('optionSelect', $.proxy(function(ev) {
                        this._openCreateVoucherModal(ev.option.getAttribute('data-id'));
                    }, this));
                }
            }

            this.addButton(this.$newVoucherBtnGroup);
        }

        // Update the URL if we're on the Vouchers index
        // ---------------------------------------------------------------------

        if (this.settings.context === 'index' && typeof history !== 'undefined') {
            var uri = 'gift-voucher/vouchers';

            if (selectedSourceHandle) {
                uri += '/'+selectedSourceHandle;
            }

            history.replaceState({}, '', Craft.getUrl(uri));
        }
    },

    _getVoucherTypeTriggerHref: function(voucherType)
    {
        if (this.settings.context === 'index') {
            var uri = 'gift-voucher/vouchers/' + voucherType.handle + '/new';
            
            if (this.siteId && this.siteId != Craft.primarySiteId) {
                for (var i = 0; i < Craft.sites.length; i++) {
                    if (Craft.sites[i].id == this.siteId) {
                        uri += '/' + Craft.sites[i].handle;
                    }
                }
            }

            return 'href="' + Craft.getUrl(uri) + '"';
        } else {
            return 'data-id="' + voucherType.id + '"';
        }
    },

    _openCreateVoucherModal: function(voucherTypeId)
    {
        if (this.$newVoucherBtn.hasClass('loading')) {
            return;
        }

        // Find the voucher type
        var voucherType;

        for (var i = 0; i < this.editableVoucherTypes.length; i++) {
            if (this.editableVoucherTypes[i].id === voucherTypeId) {
                voucherType = this.editableVoucherTypes[i];
                break;
            }
        }

        if (!voucherType) {
            return;
        }

        this.$newVoucherBtn.addClass('inactive');
        var newVoucherBtnText = this.$newVoucherBtn.text();
        this.$newVoucherBtn.text(Craft.t('gift-voucher', 'New {voucherType} voucher', { voucherType: voucherType.name }));

        new Craft.ElementEditor({
            hudTrigger: this.$newVoucherBtnGroup,
            elementType: elementTypeClass,
            siteId: this.siteId,
            attributes: {
                typeId: voucherTypeId,
            },
            onBeginLoading: $.proxy(function() {
                this.$newVoucherBtn.addClass('loading');
            }, this),
            onEndLoading: $.proxy(function() {
                this.$newVoucherBtn.removeClass('loading');
            }, this),
            onHideHud: $.proxy(function() {
                this.$newVoucherBtn.removeClass('inactive').text(newVoucherBtnText);
            }, this),
            onSaveElement: $.proxy(function(response) {
                // Make sure the right voucher type is selected
                var voucherTypeSourceKey = 'voucherType:' + voucherTypeId;

                if (this.sourceKey !== voucherTypeSourceKey) {
                    this.selectSourceByKey(voucherTypeSourceKey);
                }

                this.selectElementAfterUpdate(response.id);
                this.updateElements();
            }, this)
        });
    }
});

// Register it!
Craft.registerElementIndexClass(elementTypeClass, Craft.GiftVoucher.VoucherIndex);

})(jQuery);

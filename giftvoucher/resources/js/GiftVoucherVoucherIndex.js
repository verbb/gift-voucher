(function($){

if (typeof Craft.GiftVoucher === typeof undefined) {
	Craft.GiftVoucher = {};
}

var elementTypeClass = 'GiftVoucher_Voucher';

/**
 * Voucher index class
 */
Craft.GiftVoucher.VoucherIndex = Craft.BaseElementIndex.extend({

	voucherTypes: null,

	$newVoucherBtnGroup: null,
	$newVoucherBtn: null,

	canCreateVouchers: false,

	afterInit: function() {
		// Find which voucher types are being shown as sources
		this.voucherTypes = [];

		for (var i = 0; i < this.$sources.length; i++) {
			var $source = this.$sources.eq(i),
				key = $source.data('key'),
				match = key.match(/^voucherType:(\d+)$/);

			if (match) {
				this.voucherTypes.push({
					id: parseInt(match[1]),
					handle: $source.data('handle'),
					name: $source.text(),
					editable: $source.data('editable')
				});

				if (!this.canCreateVouchers && $source.data('editable')) {
					this.canCreateVouchers = true;
				}
			}
		}

		this.base();
	},

	getDefaultSourceKey: function() {
		// Did they request a specific voucher type in the URL?
		if (this.settings.context == 'index' && typeof defaultVoucherTypeHandle != typeof undefined) {
			for (var i = 0; i < this.$sources.length; i++) {
				var $source = $(this.$sources[i]);
				if ($source.data('handle') == defaultVoucherTypeHandle) {
					return $source.data('key');
				}
			}
		}

		return this.base();
	},

	onSelectSource: function() {
		// Get the handle of the selected source
		var selectedSourceHandle = this.$source.data('handle');

		// Update the New Voucher button
		// ---------------------------------------------------------------------

		// Remove the old button, if there is one
		if (this.$newVoucherBtnGroup) {
			this.$newVoucherBtnGroup.remove();
		}

		// Are they viewing a voucher type source?
		var selectedVoucherType;
		if (selectedSourceHandle) {
			for (var i = 0; i < this.voucherTypes.length; i++) {
				if (this.voucherTypes[i].handle == selectedSourceHandle) {
					selectedVoucherType = this.voucherTypes[i];
					break;
				}
			}
		}

		// Are they allowed to create new vouchers?
		if (this.canCreateVouchers) {
			this.$newVoucherBtnGroup = $('<div class="btngroup submit"/>');
			var $menuBtn;

			// If they are, show a primany "New voucher" button, and a dropdown of the other voucher types (if any).
			// Otherwise only show a menu button
			if (selectedVoucherType) {
				var href = this._getVoucherTypeTriggerHref(selectedVoucherType),
					label = (this.settings.context == 'index' ? Craft.t('New voucher') : Craft.t('New {voucherType} voucher', {voucherType: selectedVoucherType.name}));
				this.$newVoucherBtn = $('<a class="btn submit add icon" '+href+'>'+label+'</a>').appendTo(this.$newVoucherBtnGroup);

				if (this.settings.context != 'index') {
					this.addListener(this.$newVoucherBtn, 'click', function(ev) {
						this._openCreateVoucherModal(ev.currentTarget.getAttribute('data-id'));
					});
				}

				if (this.voucherTypes.length > 1) {
					$menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newVoucherBtnGroup);
				}
			} else {
				this.$newVoucherBtn = $menuBtn = $('<div class="btn submit add icon menubtn">'+Craft.t('New voucher')+'</div>').appendTo(this.$newVoucherBtnGroup);
			}

			if ($menuBtn) {
				var menuHtml = '<div class="menu"><ul>';

				for (var i = 0; i < this.voucherTypes.length; i++) {
					var voucherType = this.voucherTypes[i];

					if (this.settings.context == 'index' || voucherType != selectedVoucherType) {
						var href = this._getVoucherTypeTriggerHref(voucherType),
							label = (this.settings.context == 'index' ? voucherType.name : Craft.t('New {voucherType} voucher', {voucherType: voucherType.name}));
						menuHtml += '<li><a '+href+'">'+label+'</a></li>';
					}
				}

				menuHtml += '</ul></div>';

				var $menu = $(menuHtml).appendTo(this.$newVoucherBtnGroup),
					menuBtn = new Garnish.MenuBtn($menuBtn);

				if (this.settings.context != 'index') {
					menuBtn.on('optionSelect', $.proxy(function(ev) {
						this._openCreateVoucherModal(ev.option.getAttribute('data-id'));
					}, this));
				}
			}

			this.addButton(this.$newVoucherBtnGroup);
		}

		// Update the URL if we're on the Vouchers index
		// ---------------------------------------------------------------------

		if (this.settings.context == 'index' && typeof history != typeof undefined) {
			var uri = 'giftvoucher/vouchers';
			if (selectedSourceHandle) {
				uri += '/'+selectedSourceHandle;
			}
			history.replaceState({}, '', Craft.getUrl(uri));
		}

		this.base();
	},

	_getVoucherTypeTriggerHref: function(voucherType)
	{
		if (this.settings.context == 'index') {
			return 'href="'+Craft.getUrl('giftvoucher/vouchers/'+voucherType.handle+'/new')+'"';
		} else {
			return 'data-id="'+voucherType.id+'"';
		}
	},

	_openCreateVoucherModal: function(voucherTypeId)
	{
		if (this.$newVoucherBtn.hasClass('loading')) {
			return;
		}

		// Find the voucher type
		var voucherType;

		for (var i = 0; i < this.voucherTypes.length; i++) {
			if (this.voucherTypes[i].id == voucherTypeId) {
				voucherType = this.voucherTypes[i];
				break;
			}
		}

		if (!voucherType) {
			return;
		}

		this.$newVoucherBtn.addClass('inactive');
		var newVoucherBtnText = this.$newVoucherBtn.text();
		this.$newVoucherBtn.text(Craft.t('New {voucherType} voucher', {voucherType: voucherType.name}));

		new Craft.ElementEditor({
			hudTrigger: this.$newVoucherBtnGroup,
			elementType: elementTypeClass,
			locale: this.locale,
			attributes: {
				typeId: voucherTypeId
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
				var voucherTypeSourceKey = 'voucherType:'+voucherTypeId;

				if (this.sourceKey != voucherTypeSourceKey) {
					this.selectSourceByKey(voucherTypeSourceKey);
				}

				this.selectElementAfterUpdate(response.id);
				this.updateElements();
			}, this)
		});
	}
});

// Register it!
try {
	Craft.registerElementIndexClass(elementTypeClass, Craft.GiftVoucher.VoucherIndex);
}
catch(e) {
	// Already registered
}

})(jQuery);

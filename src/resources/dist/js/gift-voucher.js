!function(c){void 0===Craft.GiftVoucher&&(Craft.GiftVoucher={});var i="verbb\\giftvoucher\\elements\\Voucher";Craft.GiftVoucher.VoucherIndex=Craft.BaseElementIndex.extend({voucherTypes:null,$newVoucherBtnGroup:null,$newVoucherBtn:null,canCreateVouchers:!1,afterInit:function(){
// Find which voucher types are being shown as sources
this.voucherTypes=[];for(var e=0;e<this.$sources.length;e++){var t=this.$sources.eq(e),r,n=t.data("key").match(/^voucherType:(\d+)$/);n&&(this.voucherTypes.push({id:parseInt(n[1]),handle:t.data("handle"),name:t.text(),editable:t.data("editable")}),!this.canCreateVouchers&&t.data("editable")&&(this.canCreateVouchers=!0))}this.on("selectSource",c.proxy(this,"updateButton")),this.base()},getDefaultSourceKey:function(){
// Did they request a specific voucher type in the URL?
if("index"===this.settings.context&&"undefined"!=typeof defaultVoucherTypeHandle)for(var e=0;e<this.$sources.length;e++){var t=c(this.$sources[e]);if(t.data("handle")===defaultVoucherTypeHandle)return t.data("key")}return this.base()},updateButton:function(){
// Get the handle of the selected source
var e=this.$source.data("handle"),t;
// Update the New Voucher button
// ---------------------------------------------------------------------
// Remove the old button, if there is one
if(this.$newVoucherBtnGroup&&this.$newVoucherBtnGroup.remove(),e)for(var r=0;r<this.voucherTypes.length;r++)if(this.voucherTypes[r].handle===e){t=this.voucherTypes[r];break}
// Are they allowed to create new vouchers?
if(this.canCreateVouchers){var n;
// If they are, show a primary "New voucher" button, and a dropdown of the other voucher types (if any).
// Otherwise only show a menu button
if(this.$newVoucherBtnGroup=c('<div class="btngroup submit"/>'),t){var i=this._getVoucherTypeTriggerHref(t),o="index"===this.settings.context?Craft.t("gift-voucher","New voucher"):Craft.t("gift-voucher","New {voucherType} voucher",{voucherType:t.name});this.$newVoucherBtn=c('<a class="btn submit add icon" '+i+">"+o+"</a>").appendTo(this.$newVoucherBtnGroup),"index"!==this.settings.context&&this.addListener(this.$newVoucherBtn,"click",function(e){this._openCreateVoucherModal(e.currentTarget.getAttribute("data-id"))}),1<this.voucherTypes.length&&(n=c('<div class="btn submit menubtn"></div>').appendTo(this.$newVoucherBtnGroup))}else this.$newVoucherBtn=n=c('<div class="btn submit add icon menubtn">'+Craft.t("gift-voucher","New voucher")+"</div>").appendTo(this.$newVoucherBtnGroup);if(n){for(var h='<div class="menu"><ul>',r=0;r<this.voucherTypes.length;r++){var s=this.voucherTypes[r],i,o;if("index"===this.settings.context||s!==t)h+="<li><a "+(i=this._getVoucherTypeTriggerHref(s))+'">'+(o="index"===this.settings.context?s.name:Craft.t("gift-voucher","New {voucherType} voucher",{voucherType:s.name}))+"</a></li>"}c(h+="</ul></div>").appendTo(this.$newVoucherBtnGroup);var u=new Garnish.MenuBtn(n);"index"!==this.settings.context&&u.on("optionSelect",c.proxy(function(e){this._openCreateVoucherModal(e.option.getAttribute("data-id"))},this))}this.addButton(this.$newVoucherBtnGroup)}
// Update the URL if we're on the Vouchers index
// ---------------------------------------------------------------------
if("index"===this.settings.context&&"undefined"!=typeof history){var a="gift-voucher/vouchers";e&&(a+="/"+e),history.replaceState({},"",Craft.getUrl(a))}},_getVoucherTypeTriggerHref:function(e){return"index"===this.settings.context?'href="'+Craft.getUrl("gift-voucher/vouchers/"+e.handle+"/new")+'"':'data-id="'+e.id+'"'},_openCreateVoucherModal:function(r){if(!this.$newVoucherBtn.hasClass("loading")){for(
// Find the voucher type
var e,t=0;t<this.voucherTypes.length;t++)if(this.voucherTypes[t].id===r){e=this.voucherTypes[t];break}if(e){this.$newVoucherBtn.addClass("inactive");var n=this.$newVoucherBtn.text();this.$newVoucherBtn.text(Craft.t("gift-voucher","New {voucherType} voucher",{voucherType:e.name})),new Craft.ElementEditor({hudTrigger:this.$newVoucherBtnGroup,elementType:i,locale:this.locale,attributes:{typeId:r},onBeginLoading:c.proxy(function(){this.$newVoucherBtn.addClass("loading")},this),onEndLoading:c.proxy(function(){this.$newVoucherBtn.removeClass("loading")},this),onHideHud:c.proxy(function(){this.$newVoucherBtn.removeClass("inactive").text(n)},this),onSaveElement:c.proxy(function(e){
// Make sure the right voucher type is selected
var t="voucherType:"+r;this.sourceKey!==t&&this.selectSourceByKey(t),this.selectElementAfterUpdate(e.id),this.updateElements()},this)})}}}});
// Register it!
try{Craft.registerElementIndexClass(i,Craft.GiftVoucher.VoucherIndex)}catch(e){
// Already registered
}}(jQuery),function(r){void 0===Craft.GiftVoucher&&(Craft.GiftVoucher={});var e="verbb\\giftvoucher\\elements\\Code";
/**
 * Product index class
 */Craft.GiftVoucher.LicenseIndex=Craft.BaseElementIndex.extend({afterInit:function(){var e='href="'+Craft.getUrl("gift-voucher/codes/new")+'"',t=Craft.t("gift-voucher","New code");this.$newProductBtnGroup=r('<div class="btngroup submit"/>'),this.$newProductBtn=r('<a class="btn submit add icon" '+e+">"+t+"</a>").appendTo(this.$newProductBtnGroup),this.addButton(this.$newProductBtnGroup),this.base()}});
// Register it!
try{Craft.registerElementIndexClass(e,Craft.GiftVoucher.LicenseIndex)}catch(e){
// Already registered
}}(jQuery),// ==========================================================================
// Gift Voucher Plugin for Craft CMS
// Author: Verbb - https://verbb.io/
// ==========================================================================
// @codekit-prepend '_voucher-index.js'    
// @codekit-prepend '_code-index.js'    
void 0===Craft.GiftVoucher&&(Craft.GiftVoucher={}),jQuery;
//# sourceMappingURL=gift-voucher.js.map
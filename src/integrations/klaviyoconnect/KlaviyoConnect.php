<?php
namespace verbb\giftvoucher\integrations\klaviyoconnect;

use verbb\giftvoucher\elements\Voucher;

use Craft;

use yii\base\Component;

use fostercommerce\klaviyoconnect\events\AddLineItemCustomPropertiesEvent;

class KlaviyoConnect extends Component
{
    // Public Methods
    // =========================================================================

    public function addLineItemCustomProperties(AddLineItemCustomPropertiesEvent $e)
    {
        $eventName = $e->event;
        $order = $e->order;
        $lineItem = $e->lineItem;

        if (is_a($lineItem->purchasable, Voucher::class)) {
            $voucher = $lineItem->purchasable->voucher ?? [];

            if ($voucher) {
                $e->properties = [
                    'ProductName' => $voucher->title,
                    'Slug' => $lineItem->purchasable->voucher->slug,
                    'ProductURL' => $voucher->getUrl(),
                    'ItemPrice' => $lineItem->price,
                    'RowTotal' => $lineItem->subtotal,
                    'Quantity' => $lineItem->qty,
                    'SKU' => $lineItem->purchasable->sku,
                ];
            }
        }
    }
}

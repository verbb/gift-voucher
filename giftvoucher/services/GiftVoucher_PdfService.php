<?php

namespace Craft;

class GiftVoucher_PdfService extends BaseApplicationComponent
{
    /**
     * Get the URL to the voucher codes’s PDF file.
     *
     * @param string $orderNumber
     *
     * @return null|string
     * @throws Exception
     */
    public function getPdfUrl($orderNumber, $lineItemId = null)
    {
        $url = null;

        // Make sure the template exists
        $template = GiftVoucherHelper::getPlugin()->getSettings()->voucherCodesPdfPath;

        if (!$template) {
            return null;
        }

        // Check if voucher codes where purchased in this order
        $order = craft()->commerce_orders->getOrderByNumber($orderNumber);

        $attributes = [
            'orderId' => $order->id
        ];

        if ($lineItemId) {
            $attributes['lineItemId'] = $lineItemId;
        }

        $codes = GiftVoucherHelper::getCodesService()->getCodes($attributes);

        if (!$codes) {
            return null;
        }

        // Set Craft to the site template mode
        $templatesService = craft()->templates;
        $oldTemplateMode = $templatesService->getTemplateMode();
        $templatesService->setTemplateMode(TemplateMode::Site);

        if ($templatesService->doesTemplateExist($template)) {
            $lineItemParam = '';

            if ($lineItemId) {
                $lineItemParam = "&lineItemId={$lineItemId}";
            }

            $url = UrlHelper::getActionUrl("giftVoucher/downloads/pdf?number={$orderNumber}" . $lineItemParam);
        }

        // Restore the original template mode
        $templatesService->setTemplateMode($oldTemplateMode);

        return $url;
    }
}
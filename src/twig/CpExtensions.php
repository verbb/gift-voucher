<?php

declare(strict_types=1);

namespace verbb\giftvoucher\twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CpExtensions extends AbstractExtension {
    public function getFunctions(): array {
        return [
            new TwigFunction('giftVoucherSiteMenu', [$this, 'giftVoucherSiteMenu']),
        ];
    }

    public function giftVoucherSiteMenu(array $siteMenu, array $enabledSiteIds, string $absoluteUrl): array {
        // remove the query string from the given absolute URL
        $absoluteUrl = parse_url($absoluteUrl, PHP_URL_PATH);

        // step through
        $this->updateUrlsRecursive(
            $siteMenu,
            $absoluteUrl,
            $enabledSiteIds,
            // set the correct URL
            function (string $url, string $absoluteUrl) {
                $parsedUrl = parse_url($url);
                parse_str($parsedUrl['query'], $parsedQuery);
                $site = $parsedQuery['site'];

                return $absoluteUrl . '/' . $site . '?site=' . $site;
            },
            // set the status
            function (int|null $siteId, array $enabledSiteIds) {
                if ($siteId === null) {
                    return;
                }

                if (in_array($siteId, $enabledSiteIds)) {
                    return 'enabled';
                }

                return 'disabled';
            },
        );

        return $siteMenu;
    }

    private function updateUrlsRecursive(array &$array, string $absoluteUrl, array $enabledSiteIds, callable $urlCallback, callable $statusCallback): void {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->updateUrlsRecursive($value, $absoluteUrl, $enabledSiteIds, $urlCallback, $statusCallback);
            } elseif ($key === 'url') {
                $value = $urlCallback($value, $absoluteUrl);
            } elseif ($key === 'status') {
                $siteId = null;
                if (isset($array['attributes']['data']['site-id'])) {
                    $siteId = $array['attributes']['data']['site-id'];
                }

                $value = $statusCallback($siteId, $enabledSiteIds);
            }
        }
    }
}

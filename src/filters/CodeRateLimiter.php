<?php
namespace verbb\giftvoucher\filters;

use Craft;

use yii\filters\RateLimitInterface;
use yii\filters\RateLimiter;
use yii\web\HttpException;
use yii\web\TooManyRequestsHttpException;

class CodeRateLimiter extends RateLimiter implements RateLimitInterface
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        // Keep the identity local so this also works on earlier Craft 5 releases.
        $this->user = $this;
        $this->enableRateLimitHeaders = false;

        parent::init();
    }

    public function checkRateLimit($user, $request, $response, $action): void
    {
        $key = $this->_getCacheKey($request);
        $mutex = Craft::$app->getMutex();

        // Serialize the allowance read and write, including across the two controllers.
        if (!$mutex->acquire($key)) {
            $response->getHeaders()->set('Retry-After', '1');

            throw new TooManyRequestsHttpException($this->errorMessage);
        }

        try {
            parent::checkRateLimit($user, $request, $response, $action);
        } catch (TooManyRequestsHttpException $e) {
            $response->getHeaders()->set('Retry-After', '1');

            throw $e;
        } finally {
            $mutex->release($key);
        }
    }

    public function getRateLimit($request, $action): array
    {
        return [1, 1];
    }

    public function loadAllowance($request, $action): array
    {
        $data = Craft::$app->getCache()->get($this->_getCacheKey($request));

        return $data !== false ? $data : [1, time()];
    }

    public function saveAllowance($request, $action, $allowance, $timestamp): void
    {
        if (!Craft::$app->getCache()->set($this->_getCacheKey($request), [$allowance, $timestamp], 1)) {
            throw new HttpException(503, 'Unable to process voucher codes. Please try again later.');
        }
    }


    // Private Methods
    // =========================================================================

    private function _getCacheKey($request): string
    {
        $ip = $request->getUserIP() ?? 'unknown';
        $packedIp = inet_pton($ip);

        // Equivalent IPv6 spellings must share an allowance. Do not key by route or session.
        return 'gift-voucher:code-rate-limit:' . hash('sha256', $packedIp !== false ? bin2hex($packedIp) : $ip);
    }
}

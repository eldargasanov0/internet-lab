<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

readonly class ApiIpRateLimiter
{
    private const string MESSAGE = 'Превышен лимит запросов. Повторите попытку позже.';

    public function __construct(
        #[Autowire(service: 'limiter.api_ip')]
        private RateLimiterFactory $apiIpLimiter,
    ) {
    }

    public function assertCanRequest(string $clientIp): void
    {
        $key = trim($clientIp);
        if ($key === '') {
            $key = 'unknown';
        }

        $rateLimit = $this->apiIpLimiter->create($key)->consume();

        if ($rateLimit->isAccepted()) {
            return;
        }

        $retryAfterSeconds = max(1, $rateLimit->getRetryAfter()->getTimestamp() - time());

        throw new TooManyRequestsHttpException($retryAfterSeconds, self::MESSAGE);
    }
}

<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

readonly class ContactFeedbackRateLimiter
{
    private const string MESSAGE = 'Превышен лимит отправки обращений. Повторите попытку через минуту.';

    public function __construct(
        #[Autowire(service: 'limiter.contact_feedback')]
        private RateLimiterFactory $contactFeedbackLimiter,
    ) {
    }

    public function assertCanSubmit(string $email): void
    {
        $key = strtolower(trim($email));
        $rateLimit = $this->contactFeedbackLimiter->create($key)->consume();

        if ($rateLimit->isAccepted()) {
            return;
        }

        $retryAfterSeconds = max(1, $rateLimit->getRetryAfter()->getTimestamp() - time());

        throw new TooManyRequestsHttpException($retryAfterSeconds, self::MESSAGE);
    }
}

<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\RateLimit\ApiIpRateLimiter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class ApiIpRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ApiIpRateLimiter $apiIpRateLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 100],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $this->apiIpRateLimiter->assertCanRequest((string) $request->getClientIp());
    }
}

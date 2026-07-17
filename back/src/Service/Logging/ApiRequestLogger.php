<?php

declare(strict_types=1);

namespace App\Service\Logging;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class ApiRequestLogger
{
    public const string START_TIME_ATTRIBUTE = '_api_request_start_hrtime';

    public function __construct(
        #[Autowire(service: 'monolog.logger.api_request')]
        private LoggerInterface $logger,
    ) {
    }

    public function markStart(Request $request): void
    {
        $request->attributes->set(self::START_TIME_ATTRIBUTE, hrtime(true));
    }

    public function log(Request $request, Response $response): void
    {
        $context = [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'ip' => $request->getClientIp(),
            'status' => $response->getStatusCode(),
            'route' => $request->attributes->get('_route'),
            'duration_ms' => $this->resolveDurationMs($request),
        ];

        $query = $request->query->all();
        if ($query !== []) {
            $context['query'] = $query;
        }

        $this->logger->info('API-запрос', $context);
    }

    private function resolveDurationMs(Request $request): ?int
    {
        $start = $request->attributes->get(self::START_TIME_ATTRIBUTE);
        if (!is_int($start)) {
            return null;
        }

        return (int) round((hrtime(true) - $start) / 1_000_000);
    }
}

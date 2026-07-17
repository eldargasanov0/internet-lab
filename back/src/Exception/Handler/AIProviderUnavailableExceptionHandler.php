<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Exception\AI\AIProviderUnavailableException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Transient AI provider failure → rethrow for Messenger retry.
 */
#[AsTaggedItem(priority: 20)]
readonly class AIProviderUnavailableExceptionHandler implements MessageExceptionHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof AIProviderUnavailableException;
    }

    public function handle(\Throwable $throwable, int $feedbackId): void
    {
        if (!$throwable instanceof AIProviderUnavailableException) {
            throw new \LogicException('AIProviderUnavailableExceptionHandler вызван для неподдерживаемого исключения.');
        }

        $cause = $throwable->getPrevious() ?? $throwable;
        $this->logger->warning('AI-провайдер недоступен для ревью обратной связи; будет повтор.', [
            'feedbackId' => $feedbackId,
            'errorClass' => $cause::class,
            'errorMessage' => $throwable->getMessage(),
        ]);

        throw $throwable;
    }
}

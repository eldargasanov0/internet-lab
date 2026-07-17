<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Catch-all for unexpected Messenger failures → rethrow for retry.
 * Mirrors {@see UnexpectedExceptionHandler} (priority 0, supports all).
 */
#[AsTaggedItem(priority: 0)]
readonly class UnexpectedMessageExceptionHandler implements MessageExceptionHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function supports(\Throwable $throwable): bool
    {
        return true;
    }

    public function handle(\Throwable $throwable, int $feedbackId): void
    {
        $this->logger->warning('Неожиданный сбой AI-ревью; будет повтор.', [
            'feedbackId' => $feedbackId,
            'errorClass' => $throwable::class,
            'errorMessage' => $throwable->getMessage(),
        ]);

        throw $throwable;
    }
}

<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use Psr\Log\LoggerInterface;
use Symfony\AI\Platform\Exception\AuthenticationException;
use Symfony\AI\Platform\Exception\BadRequestException;
use Symfony\AI\Platform\Exception\ModelNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

/**
 * Permanent AI provider errors → UnrecoverableMessageHandlingException (no retry).
 */
#[AsTaggedItem(priority: 30)]
readonly class PermanentAIProviderExceptionHandler implements MessageExceptionHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof AuthenticationException
            || $throwable instanceof BadRequestException
            || $throwable instanceof ModelNotFoundException;
    }

    public function handle(\Throwable $throwable, int $feedbackId): void
    {
        if (!$this->supports($throwable)) {
            throw new \LogicException('PermanentAIProviderExceptionHandler вызван для неподдерживаемого исключения.');
        }

        $this->logger->error('Постоянная ошибка AI-провайдера; повтор не выполняется.', [
            'feedbackId' => $feedbackId,
            'errorClass' => $throwable::class,
            'errorMessage' => $throwable->getMessage(),
        ]);

        throw new UnrecoverableMessageHandlingException($throwable->getMessage(), previous: $throwable);
    }
}

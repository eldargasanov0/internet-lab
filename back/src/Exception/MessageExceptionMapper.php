<?php

declare(strict_types=1);

namespace App\Exception;

use App\Exception\Handler\MessageExceptionHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Dispatches Messenger exceptions to tagged {@see MessageExceptionHandlerInterface} handlers,
 * mirroring {@see ExceptionMapper} for the HTTP path.
 */
readonly class MessageExceptionMapper
{
    /**
     * @param iterable<MessageExceptionHandlerInterface> $handlers
     */
    public function __construct(
        #[AutowireIterator('app.message_exception_handler')]
        private iterable $handlers,
    ) {
    }

    public function handle(\Throwable $throwable, int $feedbackId): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($throwable)) {
                $handler->handle($throwable, $feedbackId);

                return;
            }
        }

        throw new \LogicException(sprintf(
            'Не найден обработчик исключения сообщения для «%s».',
            $throwable::class,
        ));
    }
}

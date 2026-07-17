<?php

declare(strict_types=1);

namespace App\Message\FailedMessage;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Dispatches exhausted Messenger failures to tagged {@see FailedMessageHandlerInterface} handlers.
 * No-ops when no handler supports the message (other consumers are unaffected).
 */
readonly class FailedMessageMapper
{
    /**
     * @param iterable<FailedMessageHandlerInterface> $handlers
     */
    public function __construct(
        #[AutowireIterator('app.failed_message_handler')]
        private iterable $handlers,
    ) {
    }

    public function handle(object $message, \Throwable $error, string $receiverName): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($message)) {
                $handler->handle($message, $error, $receiverName);

                return;
            }
        }
    }
}

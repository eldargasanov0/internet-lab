<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Message\FailedMessage\FailedMessageMapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

/**
 * Generic listener for Messenger failures that will not be retried.
 * Delegates to message-specific {@see \App\Message\FailedMessage\FailedMessageHandlerInterface} handlers.
 */
readonly class FailedMessageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FailedMessageMapper $mapper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'onMessageFailed',
        ];
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $this->mapper->handle(
            $event->getEnvelope()->getMessage(),
            $this->unwrapThrowable($event->getThrowable()),
            $event->getReceiverName(),
        );
    }

    private function unwrapThrowable(\Throwable $error): \Throwable
    {
        if ($error instanceof HandlerFailedException && $error->getPrevious() instanceof \Throwable) {
            return $error->getPrevious();
        }

        return $error;
    }
}

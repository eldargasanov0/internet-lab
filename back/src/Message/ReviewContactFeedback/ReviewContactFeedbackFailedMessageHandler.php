<?php

declare(strict_types=1);

namespace App\Message\ReviewContactFeedback;

use App\Message\FailedMessage\FailedMessageHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Logs exhausted AI-review failures after Messenger stops retrying.
 */
readonly class ReviewContactFeedbackFailedMessageHandler implements FailedMessageHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function supports(object $message): bool
    {
        return $message instanceof ReviewContactFeedbackMessage;
    }

    public function handle(object $message, \Throwable $error, string $receiverName): void
    {
        if (!$message instanceof ReviewContactFeedbackMessage) {
            return;
        }

        $this->logger->error('AI-ревью обратной связи окончательно провалилось; сообщение отправлено в транспорт отказов.', [
            'feedbackId' => $message->feedbackId,
            'errorClass' => $error::class,
            'errorMessage' => $error->getMessage(),
            'receiverName' => $receiverName,
        ]);
    }
}

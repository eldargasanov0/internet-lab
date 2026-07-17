<?php

declare(strict_types=1);

namespace App\Message\FailedMessage;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Handles Messenger messages that exhausted retries and were sent to the failure transport.
 * Parallel to {@see \App\Exception\Handler\MessageExceptionHandlerInterface}, but keyed by message type.
 */
#[AutoconfigureTag('app.failed_message_handler')]
interface FailedMessageHandlerInterface
{
    public function supports(object $message): bool;

    public function handle(object $message, \Throwable $error, string $receiverName): void;
}

<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Messenger counterpart to {@see ExceptionHandlerInterface}.
 *
 * HTTP handlers map exceptions to Problem Details; message handlers classify
 * async failures (ACK / unrecoverable / retry) via side effects and throws.
 */
#[AutoconfigureTag('app.message_exception_handler')]
interface MessageExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool;

    /**
     * Handles the exception in Messenger context.
     * May return (ACK), throw UnrecoverableMessageHandlingException, or rethrow for retry.
     */
    public function handle(\Throwable $throwable, int $feedbackId): void;
}

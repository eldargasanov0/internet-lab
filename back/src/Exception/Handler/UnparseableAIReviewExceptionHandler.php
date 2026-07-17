<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Exception\AI\UnparseableAIReviewResponseException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Unparseable AI payload → log and ACK (no persist, no retry).
 */
#[AsTaggedItem(priority: 40)]
readonly class UnparseableAIReviewExceptionHandler implements MessageExceptionHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof UnparseableAIReviewResponseException;
    }

    public function handle(\Throwable $throwable, int $feedbackId): void
    {
        if (!$throwable instanceof UnparseableAIReviewResponseException) {
            throw new \LogicException('UnparseableAIReviewExceptionHandler вызван для неподдерживаемого исключения.');
        }

        $this->logger->error('Ответ AI-ревью неразборчив; сохранение пропущено.', [
            'feedbackId' => $feedbackId,
            'errorClass' => $throwable::class,
            'errorMessage' => $throwable->getMessage(),
        ]);
    }
}

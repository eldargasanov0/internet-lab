<?php

declare(strict_types=1);

namespace App\Message\ReviewContactFeedback;

use App\Entity\UserFeedbackReview;
use App\Exception\MessageExceptionMapper;
use App\Repository\UserFeedbackRepository;
use App\Service\AI\ContactFeedbackReviewer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ReviewContactFeedbackMessageHandler
{
    public function __construct(
        private UserFeedbackRepository $userFeedbackRepository,
        private ContactFeedbackReviewer $reviewer,
        private MessageExceptionMapper $exceptionMapper,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ReviewContactFeedbackMessage $message): void
    {
        $feedback = $this->userFeedbackRepository->find($message->feedbackId);
        if ($feedback === null) {
            $this->logger->warning('Обратная связь для AI-ревью не найдена; пропуск.', [
                'feedbackId' => $message->feedbackId,
            ]);

            return;
        }

        if ($feedback->getReview() !== null) {
            $this->logger->info('Обратная связь уже имеет ревью; пропуск.', [
                'feedbackId' => $message->feedbackId,
            ]);

            return;
        }

        try {
            $result = $this->reviewer->review((string) $feedback->getComment());
        } catch (\Throwable $e) {
            $this->exceptionMapper->handle($e, $message->feedbackId);

            return;
        }

        $review = new UserFeedbackReview()
            ->setTone($result->tone)
            ->setComment($result->comment);

        $feedback->setReview($review);
        $this->entityManager->flush();
    }
}

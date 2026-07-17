<?php

declare(strict_types=1);

namespace App\Controller\API\V1\Contact\Feedback;

use App\Entity\UserFeedback;
use App\Message\ReviewContactFeedback\ReviewContactFeedbackMessage;
use App\Message\SendContactFeedbackEmails\SendContactFeedbackEmailsMessage;
use App\Service\RateLimit\ContactFeedbackRateLimiter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class ContactFeedbackHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private ContactFeedbackRateLimiter $contactFeedbackRateLimiter,
    ) {
    }

    public function __invoke(ContactFeedbackRequest $request): ContactFeedbackResponse
    {
        $this->contactFeedbackRateLimiter->assertCanSubmit($request->email);

        $feedback = new UserFeedback()
            ->setName($request->name)
            ->setPhone($request->phone)
            ->setEmail($request->email)
            ->setComment($request->comment);

        $this->entityManager->persist($feedback);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new SendContactFeedbackEmailsMessage(
            id: $feedback->getId(),
            name: $feedback->getName(),
            phone: $feedback->getPhone(),
            email: $feedback->getEmail(),
            comment: $feedback->getComment(),
        ));

        $this->messageBus->dispatch(new ReviewContactFeedbackMessage(
            feedbackId: $feedback->getId(),
        ));

        return new ContactFeedbackResponse(
            id: $feedback->getId(),
            name: $feedback->getName(),
            phone: $feedback->getPhone(),
            email: $feedback->getEmail(),
            comment: $feedback->getComment()
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\API\V1\Contact\Feedback;

use App\Entity\UserFeedback;
use Doctrine\ORM\EntityManagerInterface;

readonly class ContactFeedbackHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(ContactFeedbackRequest $request): ContactFeedbackResponse
    {
        $feedback = new UserFeedback()
            ->setName($request->name)
            ->setPhone($request->phone)
            ->setEmail($request->email)
            ->setComment($request->comment);

        $this->entityManager->persist($feedback);
        $this->entityManager->flush();

        return new ContactFeedbackResponse(
            id: $feedback->getId(),
            name: $feedback->getName(),
            phone: $feedback->getPhone(),
            email: $feedback->getEmail(),
            comment: $feedback->getComment()
        );
    }
}

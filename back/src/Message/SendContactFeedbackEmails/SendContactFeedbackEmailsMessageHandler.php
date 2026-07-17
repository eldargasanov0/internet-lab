<?php

declare(strict_types=1);

namespace App\Message\SendContactFeedbackEmails;

use App\Service\Mailer\ContactFeedbackMailer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendContactFeedbackEmailsMessageHandler
{
    public function __construct(
        private ContactFeedbackMailer $mailer,
    ) {
    }

    public function __invoke(SendContactFeedbackEmailsMessage $message): void
    {
        $this->mailer->send($message);
    }
}

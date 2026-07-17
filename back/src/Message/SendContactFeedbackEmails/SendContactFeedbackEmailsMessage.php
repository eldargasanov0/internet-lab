<?php

declare(strict_types=1);

namespace App\Message\SendContactFeedbackEmails;

readonly class SendContactFeedbackEmailsMessage
{
    public function __construct(
        public int $id,
        public string $name,
        public string $phone,
        public string $email,
        public string $comment,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\API\V1\Contact\Feedback;

class ContactFeedbackResponse
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

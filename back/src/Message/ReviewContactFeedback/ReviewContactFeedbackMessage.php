<?php

declare(strict_types=1);

namespace App\Message\ReviewContactFeedback;

readonly class ReviewContactFeedbackMessage
{
    public function __construct(
        public int $feedbackId,
    ) {
    }
}

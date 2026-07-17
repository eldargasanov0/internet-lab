<?php

declare(strict_types=1);

namespace App\DTO\Ai;

use App\Enum\UserFeedbackToneEnum;

readonly class ContactFeedbackReviewResult
{
    public function __construct(
        public UserFeedbackToneEnum $tone,
        public string $comment,
    ) {
    }
}

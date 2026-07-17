<?php

declare(strict_types=1);

namespace App\Enum;

enum UserFeedbackToneEnum: string
{
    case POSITIVE = 'positive';
    case NEUTRAL = 'neutral';
    case NEGATIVE = 'negative';
    case MIXED = 'mixed';
}

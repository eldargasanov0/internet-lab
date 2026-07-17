<?php

declare(strict_types=1);

namespace App\Exception\AI;

/**
 * Thrown when the AI agent returns a payload that cannot be mapped to a review result.
 * Callers (Messenger handler) should treat this as a permanent failure and skip persistence.
 */
final class UnparseableAIReviewResponseException extends \RuntimeException
{
}

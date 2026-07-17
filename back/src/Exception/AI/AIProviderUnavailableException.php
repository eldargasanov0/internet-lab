<?php

declare(strict_types=1);

namespace App\Exception\AI;

/**
 * Transient AI provider failure (timeout, network, 5xx, rate limit).
 * Callers should let Messenger retry; do not persist a review row.
 */
final class AIProviderUnavailableException extends \RuntimeException
{
}

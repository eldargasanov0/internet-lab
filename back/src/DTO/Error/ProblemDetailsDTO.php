<?php

declare(strict_types=1);

namespace App\DTO\Error;

/**
 * Базовый DTO ответа об ошибке по RFC 7807 (Problem Details).
 */
abstract readonly class ProblemDetailsDTO
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public string $detail,
        public ?string $instance = null,
        public array $headers = [],
    ) {
    }
}

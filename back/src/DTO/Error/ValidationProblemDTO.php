<?php

declare(strict_types=1);

namespace App\DTO\Error;

use App\Enum\ExceptionProblemEnum;

readonly class ValidationProblemDTO extends ProblemDetailsDTO
{
    /**
     * @param array<string, list<string>> $violations
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        int $status,
        ?string $instance = null,
        array $headers = [],
        public array $violations,
    ) {
        parent::__construct(
            type: ExceptionProblemEnum::VALIDATION->value,
            title: ExceptionProblemEnum::VALIDATION->getTitle(),
            status: $status,
            detail: ExceptionProblemEnum::VALIDATION->getTitle(),
            instance: $instance,
            headers: $headers,
        );
    }
}

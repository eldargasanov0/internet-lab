<?php

declare(strict_types=1);

namespace App\DTO\Error;

use App\Enum\ExceptionProblemType;

readonly class HttpProblemDTO extends ProblemDetailsDTO
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        int $status,
        string $detail,
        ?string $instance = null,
        array $headers = [],
    ) {
        parent::__construct(
            type: ExceptionProblemType::REQUEST->value,
            title: ExceptionProblemType::REQUEST->getTitle(),
            status: $status,
            detail: $detail,
            instance: $instance,
            headers: $headers,
        );
    }
}

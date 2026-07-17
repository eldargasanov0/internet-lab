<?php

declare(strict_types=1);

namespace App\DTO\Error;

use App\Enum\ExceptionProblemEnum;

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
            type: ExceptionProblemEnum::REQUEST->value,
            title: ExceptionProblemEnum::REQUEST->getTitle(),
            status: $status,
            detail: $detail,
            instance: $instance,
            headers: $headers,
        );
    }
}

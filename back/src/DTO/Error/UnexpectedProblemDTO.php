<?php

declare(strict_types=1);

namespace App\DTO\Error;

use App\Enum\ExceptionProblemType;
use Symfony\Component\HttpFoundation\Response;

readonly class UnexpectedProblemDTO extends ProblemDetailsDTO
{
    public function __construct(
        ?string $detail = null,
        ?string $instance = null,
    ) {
        parent::__construct(
            type: ExceptionProblemType::INTERNAL->value,
            title: ExceptionProblemType::INTERNAL->getTitle(),
            status: Response::HTTP_INTERNAL_SERVER_ERROR,
            detail: $detail ?? ExceptionProblemType::INTERNAL->getTitle(),
            instance: $instance,
        );
    }
}

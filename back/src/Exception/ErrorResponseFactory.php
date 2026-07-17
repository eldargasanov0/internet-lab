<?php

declare(strict_types=1);

namespace App\Exception;

use App\DTO\Error\ProblemDetailsDTO;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class ErrorResponseFactory
{
    public function create(ProblemDetailsDTO $problem): JsonResponse
    {
        $response = new JsonResponse(
            $problem,
            $problem->status,
            ['Content-Type' => 'application/problem+json'],
        );

        $response->headers->add($problem->headers);

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\DTO\Error\ProblemDetailsDTO;
use App\DTO\Error\ValidationProblemDTO;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsTaggedItem(priority: 30)]
readonly class ValidationExceptionHandler implements ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool
    {
        return $this->extractValidationException($throwable) !== null;
    }

    public function handle(\Throwable $throwable, ?string $instance = null): ProblemDetailsDTO
    {
        $validation = $this->extractValidationException($throwable);
        if ($validation === null) {
            throw new \LogicException('ValidationExceptionHandler вызван для неподдерживаемого исключения.');
        }

        $violations = [];
        foreach ($validation->getViolations() as $violation) {
            $violations[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        $status = $throwable instanceof HttpExceptionInterface
            ? $throwable->getStatusCode()
            : Response::HTTP_UNPROCESSABLE_ENTITY;

        $headers = $throwable instanceof HttpExceptionInterface
            ? $throwable->getHeaders()
            : [];

        return new ValidationProblemDTO(
            status: $status,
            instance: $instance,
            headers: $headers,
            violations: $violations,
        );
    }

    private function extractValidationException(\Throwable $throwable): ?ValidationFailedException
    {
        if ($throwable instanceof ValidationFailedException) {
            return $throwable;
        }

        $previous = $throwable->getPrevious();
        if ($throwable instanceof HttpExceptionInterface && $previous instanceof ValidationFailedException) {
            return $previous;
        }

        return null;
    }
}

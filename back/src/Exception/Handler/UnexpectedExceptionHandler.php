<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\DTO\Error\ProblemDetailsDTO;
use App\DTO\Error\UnexpectedProblemDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 0)]
readonly class UnexpectedExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function supports(\Throwable $throwable): bool
    {
        return true;
    }

    public function handle(\Throwable $throwable, ?string $instance = null): ProblemDetailsDTO
    {
        $this->logger->error('Необработанное исключение API', [
            'exception' => $throwable,
        ]);

        return new UnexpectedProblemDTO(
            instance: $instance,
        );
    }
}

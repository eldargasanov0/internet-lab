<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\DTO\Error\ProblemDetailsDTO;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.exception_handler')]
interface ExceptionHandlerInterface
{
    public function supports(\Throwable $throwable): bool;

    public function handle(\Throwable $throwable, ?string $instance = null): ProblemDetailsDTO;
}

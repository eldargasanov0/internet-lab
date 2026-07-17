<?php

declare(strict_types=1);

namespace App\Exception;

use App\DTO\Error\ProblemDetailsDTO;
use App\Exception\Handler\ExceptionHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class ExceptionMapper
{
    /**
     * @param iterable<ExceptionHandlerInterface> $handlers
     */
    public function __construct(
        #[AutowireIterator('app.exception_handler')]
        private iterable $handlers,
    ) {
    }

    public function map(\Throwable $throwable, ?string $instance = null): ProblemDetailsDTO
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($throwable)) {
                return $handler->handle($throwable, $instance);
            }
        }

        throw new \LogicException(sprintf(
            'Не найден обработчик исключения для «%s».',
            $throwable::class,
        ));
    }
}

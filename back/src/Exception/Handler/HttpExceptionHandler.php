<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\DTO\Error\HttpProblemDTO;
use App\DTO\Error\ProblemDetailsDTO;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsTaggedItem(priority: 20)]
readonly class HttpExceptionHandler implements ExceptionHandlerInterface
{
    private const array STATUS_TEXTS = [
        Response::HTTP_BAD_REQUEST => 'Некорректный запрос',
        Response::HTTP_UNAUTHORIZED => 'Требуется аутентификация',
        Response::HTTP_FORBIDDEN => 'Доступ запрещён',
        Response::HTTP_NOT_FOUND => 'Не найдено',
        Response::HTTP_METHOD_NOT_ALLOWED => 'Метод не поддерживается',
        Response::HTTP_CONFLICT => 'Конфликт',
        Response::HTTP_UNPROCESSABLE_ENTITY => 'Ошибка валидации',
        Response::HTTP_TOO_MANY_REQUESTS => 'Слишком много запросов',
        Response::HTTP_INTERNAL_SERVER_ERROR => 'Внутренняя ошибка сервера',
        Response::HTTP_SERVICE_UNAVAILABLE => 'Сервис недоступен',
    ];

    public function supports(\Throwable $throwable): bool
    {
        return $throwable instanceof HttpExceptionInterface;
    }

    public function handle(\Throwable $throwable, ?string $instance = null): ProblemDetailsDTO
    {
        if (!$throwable instanceof HttpExceptionInterface) {
            throw new \LogicException('HttpExceptionHandler вызван для неподдерживаемого исключения.');
        }

        $detail = $throwable->getMessage();
        if ($detail === '') {
            $detail = self::STATUS_TEXTS[$throwable->getStatusCode()] ?? 'Ошибка';
        }

        return new HttpProblemDTO(
            status: $throwable->getStatusCode(),
            detail: $detail,
            instance: $instance,
            headers: $throwable->getHeaders(),
        );
    }
}

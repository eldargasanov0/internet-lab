<?php

declare(strict_types=1);

namespace App\Enum;

enum ExceptionProblemEnum: string
{
    case VALIDATION = 'errors.validation';
    case REQUEST = 'errors.request';
    case INTERNAL = 'errors.internal';

    public function getTitle(): string
    {
        return match ($this) {
            self::VALIDATION => 'Ошибка валидации',
            self::REQUEST => 'Ошибка запроса',
            self::INTERNAL => 'Произошла непредвиденная ошибка',
        };
    }
}

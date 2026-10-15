<?php

namespace App\Enums;

enum CourseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    /**
     * Получить все строковые значения перечисления.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Получить читаемые названия статусов.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Draft->value => 'Черновик',
            self::Published->value => 'Опубликован',
        ];
    }
}

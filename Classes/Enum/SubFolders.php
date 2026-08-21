<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Enum;

enum SubFolders: string
{
    case NONE = 'none';
    case YEAR = 'year';
    case MONTH = 'month';

    public function allowYear(): bool
    {
        return $this === self::YEAR || $this === self::MONTH;
    }

    public function allowMonth(): bool
    {
        return $this === self::MONTH;
    }
}

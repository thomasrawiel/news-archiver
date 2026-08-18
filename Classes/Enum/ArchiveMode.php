<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Enum;

enum ArchiveMode: string
{
    case AMOUNT = 'amount';
    case AGE = 'age';
}

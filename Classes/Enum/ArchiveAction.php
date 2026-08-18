<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Enum;

enum ArchiveAction: string
{
    case MOVE = 'move';
    case ARCHIVE = 'archive';
    case BOTH = 'both';

    public function allowsMove(): bool
    {
        return $this === self::BOTH || $this === self::MOVE;
    }

    public function allowsArchive(): bool
    {
        return $this === self::BOTH || $this === self::ARCHIVE;
    }
}

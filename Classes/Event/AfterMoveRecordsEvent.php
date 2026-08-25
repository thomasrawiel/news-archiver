<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Event;

use TRAW\NewsArchiver\Domain\DTO\Configuration;

final readonly class AfterMoveRecordsEvent
{
    public function __construct(private array $moveCommand, private Configuration $configuration)
    {
    }

    public function getMoveCommand(): array
    {
        return $this->moveCommand;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }
}

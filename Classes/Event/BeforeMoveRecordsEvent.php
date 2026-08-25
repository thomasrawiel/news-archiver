<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Event;

use TRAW\NewsArchiver\Domain\DTO\Configuration;

final class BeforeMoveRecordsEvent
{
    public function __construct(private array $moveCommand, private readonly Configuration $configuration) {}

    public function getMoveCommand(): array
    {
        return $this->moveCommand;
    }

    public function setMoveCommand(array $moveCommand): void
    {
        $this->moveCommand = $moveCommand;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }
}

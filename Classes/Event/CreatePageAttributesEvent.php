<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Event;

use TRAW\NewsArchiver\Domain\DTO\Configuration;

final class CreatePageAttributesEvent
{
    public function __construct(private array $pageAttributes, private readonly Configuration $configuration)
    {

    }

    public function getPageAttributes(): array
    {
        return $this->pageAttributes;
    }

    public function setPageAttributes(array $pageAttributes): void
    {
        $this->pageAttributes = $pageAttributes;
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }
}

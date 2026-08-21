<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Event;

final class CreatePageAttributesEvent
{
    public function __construct(private array $pageAttributes) {

    }

    public function getPageAttributes(): array
    {
        return $this->pageAttributes;
    }

    public function setPageAttributes(array $pageAttributes): void
    {
        $this->pageAttributes = $pageAttributes;
    }
}

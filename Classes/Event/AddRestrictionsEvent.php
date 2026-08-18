<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Event;

use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;

class AddRestrictionsEvent
{
    public function __construct(private array $restrictions = [])
    {
    }

    public function getRestrictions(): array
    {
        return $this->restrictions;
    }

    public function setRestrictions(array $restrictions): void
    {
        $this->restrictions = $restrictions;
    }

    public function addRestriction(QueryRestrictionInterface $restriction): void
    {
        $this->restrictions[] = $restriction;
    }
}

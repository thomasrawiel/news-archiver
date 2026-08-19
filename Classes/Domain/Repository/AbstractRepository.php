<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Domain\Repository;

use TRAW\NewsArchiver\Event\AddRestrictionsEvent;
use TRAW\NewsArchiver\Event\RemoveRestrictionsEvent;
use TRAW\NewsArchiver\Utility\PidUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract readonly class AbstractRepository
{
    public function __construct(
        protected ConnectionPool  $connectionPool,
        protected EventDispatcher $eventDispatcher,
        protected PidUtility      $pidUtility
    )
    {
    }

    protected function createQueryBuilder(string $tableName): QueryBuilder
    {
        $qb = $this->connectionPool->getQueryBuilderForTable($tableName);
        $qb->getRestrictions()->removeAll();

        $removeRestrictions = $this->eventDispatcher->dispatch(new RemoveRestrictionsEvent($tableName));
        foreach ($removeRestrictions->getRestrictions() as $restriction) {
            if (is_a($restriction, QueryRestrictionInterface::class, true)) {
                $qb->getRestrictions()->removeByType($restriction);
            }
        }
        $addRestrictions = $this->eventDispatcher->dispatch(new AddRestrictionsEvent($tableName, [DeletedRestriction::class]));
        foreach ($addRestrictions->getRestrictions() as $restriction) {
            if (is_a($restriction, QueryRestrictionInterface::class, true)) {
                $qb->getRestrictions()->add(
                    GeneralUtility::makeInstance($restriction)
                );
            }
        }
        return $qb;
    }
}

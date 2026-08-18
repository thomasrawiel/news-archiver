<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use TRAW\NewsArchiver\Domain\DTO\Configuration;
use TRAW\NewsArchiver\Event\AddRestrictionsEvent;
use TRAW\NewsArchiver\Event\RemoveRestrictionsEvent;
use TRAW\NewsArchiver\Utility\PidUtility;
use TRAW\NewsArchiver\Utility\TreeListUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class NewsRepository
{
    public function __construct(
        private ConnectionPool  $connectionPool,
        private EventDispatcher $eventDispatcher,
        private PidUtility      $pidUtility
    )
    {
    }

    public function fetchNews(Configuration $configuration): array
    {
        if (!$configuration->isEnabled()) {
            return [];
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tx_news_domain_model_news');
        $qb->getRestrictions()->removeAll();

        $removeRestrictions = $this->eventDispatcher->dispatch(new RemoveRestrictionsEvent());
        foreach ($removeRestrictions->getRestrictions() as $restriction) {
            if (is_a($restriction, QueryRestrictionInterface::class, true)) {
                $qb->getRestrictions()->removeByType($restriction);
            }
        }
        $addRestrictions = $this->eventDispatcher->dispatch(new AddRestrictionsEvent([DeletedRestriction::class]));
        foreach ($addRestrictions->getRestrictions() as $restriction) {
            if (is_a($restriction, QueryRestrictionInterface::class, true)) {
                $qb->getRestrictions()->add(
                    GeneralUtility::makeInstance($restriction)
                );
            }
        }

        $constraints = [];
        $expr = $qb->expr();

        if (!$configuration->ignorePids()) {
            $constraints[] = $expr->in('pid', $qb->createNamedParameter($this->pidUtility->getStoragePids(), ArrayParameterType::INTEGER));
        }

        if (!$configuration->archiveAllNews()) {
            if ($configuration->archiveNewsByAge()) {
                //older than x days
                $cutoffTimestamp = max(0, time() - (86400 * $configuration->getArchiveNewsAmount()));
                $constraints[] = $expr->lte('datetime', $qb->createNamedParameter($cutoffTimestamp, ParameterType::INTEGER));
            }

            if ($configuration->archiveNewsByAmount()) {
                //offset, we want to keep the first x-amount records
                $qb->setFirstResult($configuration->getArchiveNewsAmount());
            }
        }

        //only records in default, all or without default language
        $constraints[] = $expr->or(
            $qb->expr()->in('sys_language_uid', $qb->createNamedParameter([0, -1], ArrayParameterType::INTEGER)),
            $expr->and(
                $expr->gt('sys_language_uid', $qb->createNamedParameter(0, ParameterType::INTEGER)),
                $expr->eq('l10n_parent', $qb->createNamedParameter(0, ParameterType::INTEGER))
            )
        );

        $qb->select('*')
            ->from('tx_news_domain_model_news')
            ->where(...$constraints)
            ->orderBy('datetime', 'DESC');

        try {
            return $qb->executeQuery()->fetchAllAssociative();
        } catch (\Throwable) {
            return [];
        }
    }
}

<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use TRAW\NewsArchiver\Domain\DTO\Configuration;

final readonly class NewsRepository extends AbstractRepository
{
    public const string TABLE = 'tx_news_domain_model_news';

    public function fetchNews(Configuration $configuration): array
    {
        if (!$configuration->isEnabled()) {
            return [];
        }

        $qb = $this->createQueryBuilder(self::TABLE);

        $constraints = [];
        $expr = $qb->expr();

        if (!$configuration->ignorePids()) {
            $pids = $this->pidUtility->getStoragePids($configuration->getNewsRootFolder(), $configuration->getRecursive());
            $constraints[] = $expr->in('pid', $qb->createNamedParameter($pids, ArrayParameterType::INTEGER));
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
        $l10nParent = $GLOBALS['TCA'][self::TABLE]['ctrl']['transOrigPointerField'];
        $constraints[] = $expr->or(
            $qb->expr()->in('sys_language_uid', $qb->createNamedParameter([0, -1], ArrayParameterType::INTEGER)),
            $expr->and(
                $expr->gt('sys_language_uid', $qb->createNamedParameter(0, ParameterType::INTEGER)),
                $expr->eq($l10nParent, $qb->createNamedParameter(0, ParameterType::INTEGER))
            )
        );

        $qb->select('*')
            ->from(self::TABLE)
            ->where(...$constraints)
            ->orderBy('datetime', 'ASC')
            ->setMaxResults($configuration->getLimit());

        try {
            return $qb->executeQuery()->fetchAllAssociative();
        } catch (\Throwable) {
            return [];
        }
    }

    public function setArchiveDate(int $newsUid, int $archiveDate): int
    {
        $l10nParent = $GLOBALS['TCA'][self::TABLE]['ctrl']['transOrigPointerField'];
        $qb = $this->createQueryBuilder(self::TABLE);
        try {
            return $qb->update(self::TABLE)
                ->set('archive', $archiveDate, true, ParameterType::INTEGER)
                ->where(
                    $qb->expr()->or(
                        $qb->expr()->eq('uid', $qb->createNamedParameter($newsUid, ParameterType::INTEGER)),
                        $qb->expr()->eq($l10nParent, $qb->createNamedParameter($newsUid, ParameterType::INTEGER)),
                    )
                )->executeStatement();
        } catch (\Throwable) {
            return 0;
        }
    }
}

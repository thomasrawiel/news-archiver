<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Domain\Repository;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Backend\Utility\BackendUtility;

final readonly class PageRepository extends AbstractRepository
{
    public const string TABLE = 'pages';

    public function getPageRecord(int $uid): array
    {
        return BackendUtility::getRecord(self::TABLE, $uid) ?? [];
    }

    public function findPageByTitleAndPid(string $title, int $pid): int
    {
        $qb = $this->createQueryBuilder(self::TABLE);
        $expr = $qb->expr();
        try {
            return $qb->select('uid')
                ->from(self::TABLE)
                ->where(
                    $expr->eq('title', $qb->createNamedParameter($title)),
                    $expr->eq('pid', $qb->createNamedParameter($pid, ParameterType::INTEGER)),
                )->executeQuery()
                ->fetchOne() ? : 0;
        } catch (\Throwable) {
            return 0;
        }
    }
}

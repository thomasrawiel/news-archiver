<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Utility;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class TreeListUtility
{
    public function getTreeListFromId($id, $depth, $begin = 0): string
    {
        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin === 0) {
            $theList = $id;
        } else {
            $theList = '';
        }

        $sortBy = $GLOBALS['TCA']['pages']['ctrl']['sortby'] ?? false;

        if ($id && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy($sortBy?:'uid');

            $statement = $queryBuilder->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = $this->getTreeListFromId($row['uid'], $depth - 1, $begin - 1);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }
        return (string)$theList;
    }


    public function getTreeListFromArray(array $pids, int $depth, int $begin = 0): string
    {
        $list = '';
        for ($i = 0; $i < count($pids); $i++) {
            if ($i > 0) {
                $list .= ',';
            }
            $list .= $this->getTreeListFromId($pids[$i], $depth, $begin);
        }

        return $list;
    }

    public function getTreeListArrayFromArray(array $pids, int $depth, int $begin = 0): array
    {
        return GeneralUtility::trimExplode(',', $this->getTreeListFromArray($pids, $depth, $begin), true);
    }

    public function getTreeListArray($id, $depth, $begin = 0): array
    {
        return GeneralUtility::trimExplode(',', $this->getTreeListFromId($id, $depth, $begin), true);
    }
}

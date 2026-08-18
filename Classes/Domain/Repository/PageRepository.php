<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Domain\Repository;

use TYPO3\CMS\Backend\Utility\BackendUtility;

class PageRepository
{
    public function __construct(
    )
    {
    }

    public function getPageRecord(int $uid): array {
        return BackendUtility::getRecord('pages', $uid);
    }
}

<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class PidUtility
{
    public function __construct(
        private TreeListUtility      $treeListUtility
    )
    {
    }

    public function getStoragePids(string $newsRootFolder, int $recursive): array
    {
        $rootFolders = array_map(
            static fn(string $value): int => (int)$value,
            array_filter(
                GeneralUtility::trimExplode(',', $newsRootFolder),
                //only positive decimal/ numerical values = int+
                static fn(string $value): bool => ctype_digit($value)
            )
        );

        return $this->treeListUtility->getTreeListArrayFromArray($rootFolders, $recursive);
    }
}

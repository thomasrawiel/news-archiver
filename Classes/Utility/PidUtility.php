<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class PidUtility
{
    public function __construct(
        private ConfigurationUtility $configurationUtility,
        private TreeListUtility      $treeListUtility
    )
    {
    }

    public function getStoragePids(): array
    {
        $configuration = $this->configurationUtility->getConfiguration();

        $rootFolders = array_map(
            static fn(string $value): int => (int)$value,
            array_filter(
                GeneralUtility::trimExplode(',', $configuration->getNewsRootFolder()),
                //only positive decimal/ numerical values = int+
                static fn(string $value): bool => ctype_digit($value)
            )
        );

        return $this->treeListUtility->getTreeListArrayFromArray($rootFolders, $configuration->getRecursive());
    }
}

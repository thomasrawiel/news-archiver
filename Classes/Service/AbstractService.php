<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Service;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract readonly class AbstractService
{
    protected function runDataHandler(array $dataMap = [], array $cmdMap = []): ?array
    {
        if ($cmdMap === [] && $dataMap === []) {
            return null;
        }

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, $cmdMap);

        if ($cmdMap !== []) {
            $dataHandler->process_cmdmap();
        }

        if ($dataMap !== []) {
            $dataHandler->process_datamap();
            return $dataHandler->substNEWwithIDs;
        }

        return null;
    }

    protected function moveRecord(int $recordUid, int $moveToUid, string $table, array &$cmd): void
    {
        $cmd[$table][$recordUid]['move'] = $moveToUid;
    }
}

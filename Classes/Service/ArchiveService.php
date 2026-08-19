<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Service;

use Symfony\Component\Console\Style\SymfonyStyle;
use TRAW\NewsArchiver\Domain\Repository\NewsRepository;
use TRAW\NewsArchiver\Domain\Repository\PageRepository;
use TRAW\NewsArchiver\Utility\ConfigurationUtility;

final readonly class ArchiveService extends AbstractService
{
    public function __construct(
        protected ConfigurationUtility $configurationUtility,
        protected NewsRepository       $newsRepository,
        protected PageService          $pageService,
        protected PageRepository       $pageRepository
    )
    {
    }

    public function archive(SymfonyStyle $io): void
    {
        $configuration = $this->configurationUtility->getConfiguration();

        $news = $this->newsRepository->fetchNews($configuration);
        if ($news === []) {
            return;
        }

        if($io->isVerbose()) {
            $io->info('Found '.count($news).' news');
        }

        $pidMap = $this->pageService->getPidMap($news, $io);

        $moveCommand = [];

        foreach ($news as $record) {
            $recordUid = $record['uid'];
            if ($configuration->isArchiveModeMove() && isset($pidMap[$recordUid])) {
                $this->moveRecord($recordUid, $pidMap[$recordUid], NewsRepository::TABLE, $moveCommand);
                if($io->isVeryVerbose()) {
                    $io->writeln("Moving record [$recordUid] to page [$pidMap[$recordUid]]");
                }
            }

            if ($configuration->isArchiveModeArchive()) {
                $updated = $this->setArchiveDate($record);
                if($io->isVeryVerbose()) {
                    $io->writeln(($updated ? "Set archive date to NOW" : "Skippped setting archive date"). "for record uid [$recordUid]");
                }
            }
        }

        if($io->isVerbose()) {
            $io->info('Executing datahandler for '.$configuration->getLimit().' records. This may take a while...');
        }

        $this->runDataHandler([], $moveCommand);
    }

    private function setArchiveDate(array $newsRecord): bool
    {
        $archiveDate = time();

        if ((int)$newsRecord['archive'] === 0) {
            return (bool)$this->newsRepository->setArchiveDate($newsRecord['uid'], $archiveDate);
        }

        return false;
    }
}

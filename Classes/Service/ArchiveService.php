<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Service;

use Symfony\Component\Console\Style\SymfonyStyle;
use TRAW\NewsArchiver\Domain\DTO\Configuration;
use TRAW\NewsArchiver\Domain\Repository\NewsRepository;
use TRAW\NewsArchiver\Domain\Repository\PageRepository;
use TRAW\NewsArchiver\Event\AfterMoveRecordsEvent;
use TRAW\NewsArchiver\Event\BeforeMoveRecordsEvent;
use TRAW\NewsArchiver\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

final class ArchiveService extends AbstractService
{
    public function __construct(
        protected readonly ConfigurationUtility $configurationUtility,
        protected readonly NewsRepository       $newsRepository,
        protected readonly PageService          $pageService,
        protected readonly PageRepository       $pageRepository,
        protected readonly EventDispatcher      $eventDispatcher
    )
    {
    }

    public function archive(SymfonyStyle $io, ?Configuration $configuration = null): void
    {
        if ($configuration === null) {
            $this->configuration = $this->configurationUtility->getConfiguration();
        } else {
            $this->configuration = $configuration;
        }
        $this->pageService->setConfiguration($this->configuration);

        $news = $this->newsRepository->fetchNews($this->configuration);
        if ($news === []) {
            return;
        }

        if ($io->isVerbose()) {
            $io->info('Found ' . count($news) . ' news');
        }

        $pidMap = $this->pageService->getPidMap($news, $io);

        $moveCommand = [];

        foreach ($news as $record) {
            $recordUid = $record['uid'];
            if ($this->configuration->isArchiveActionMove() && isset($pidMap[$recordUid])) {
                $this->moveRecord($recordUid, $pidMap[$recordUid], NewsRepository::TABLE, $moveCommand);
                if ($io->isVeryVerbose()) {
                    $io->writeln("Moving record [$recordUid] to page [$pidMap[$recordUid]]");
                }
            }

            if ($this->configuration->isArchiveActionArchive()) {
                $updated = $this->setArchiveDate($record);
                if ($io->isVeryVerbose()) {
                    $io->writeln(($updated ? "Set archive date to NOW" : "Skippped setting archive date") . "for record uid [$recordUid]");
                }
            }
        }

        if ($io->isVerbose()) {
            $io->info('Executing datahandler for ' . count($moveCommand[NewsRepository::TABLE]) . ' records. This may take a while...');
        }

        $chunks = array_chunk($moveCommand[NewsRepository::TABLE], 20, true);
        $pb = $io->createProgressBar(count($chunks));

        foreach ($chunks as $chunk) {
            $cmd = [];
            $cmd[NewsRepository::TABLE] = $chunk;
            $cmd = $this->eventDispatcher->dispatch(new BeforeMoveRecordsEvent($cmd, $this->configuration))->getMoveCommand();
            $this->runDataHandler([], $cmd);
            $this->eventDispatcher->dispatch(new AfterMoveRecordsEvent($cmd, $this->configuration));
            $pb->advance();
        }
        $pb->finish();
        $io->newLine();

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

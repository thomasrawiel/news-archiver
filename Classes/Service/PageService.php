<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Service;

use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use TRAW\NewsArchiver\Domain\DTO\Configuration;
use TRAW\NewsArchiver\Domain\Repository\NewsRepository;
use TRAW\NewsArchiver\Domain\Repository\PageRepository;
use TRAW\NewsArchiver\Event\CreatePageAttributesEvent;
use TRAW\NewsArchiver\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

final class PageService extends AbstractService
{

    public function __construct(
        private readonly PageRepository  $pageRepository,
        private readonly EventDispatcher $eventDispatcher,
    )
    {
    }

    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getPidMap(array $news, SymfonyStyle $io): array
    {
        if ($this->configuration->getTargetPid() === 0) {
            throw new \LogicException('Archive target page is missing from the extension\'s settigs');
        }

        $pidMap = [];
        $years = [];
        $months = [];

        $entryPoint = $this->configuration->getTargetPid();
        $languages = $this->getLanguages($entryPoint);

        foreach ($news as $record) {
            $pidMap[$record['uid']] = $entryPoint;

            if ($this->configuration->createYearSubfolders()) {
                $d = (new \DateTimeImmutable())->setTimestamp($record['datetime']);
                $year = $d->format('Y');
                $month = $d->format('m');

                $newPid = $this->pageRepository->findPageByTitleAndPid($year, $entryPoint);

                if ($newPid === 0) {
                    $previousYears = array_filter(
                        array_keys($years),
                        static fn(int $existingYear): bool => $existingYear < $year
                    );
                    $insertPid = $previousYears !== []
                        ? ($years[max($previousYears)] * -1)
                        : $entryPoint;

                    $newPid = $this->createPage($year, $insertPid);
                    $translations = $this->translatePage($newPid, $languages);

                    if ($io->isVerbose()) {
                        $io->success("Created new page $newPid for $year");
                        $io->comment("... and $translations translations");
                    }
                }

                $pidMap[$record['uid']] = $newPid;
                $years[$year] = $newPid;

                if ($this->configuration->createMonthSubfolders()) {
                    $newPid = $this->pageRepository->findPageByTitleAndPid($month, $years[$year]);

                    if ($newPid === 0) {
                        $previousMonths = array_filter(
                            array_keys($months[$year] ?? []),
                            static fn(string $existingMonth): bool => $existingMonth < $month
                        );

                        $insertPid = $previousMonths !== []
                            ? $months[$year][max($previousMonths)] * -1
                            : $years[$year];

                        $newPid = $this->createPage($month, $insertPid);
                        $translations = $this->translatePage($newPid, $languages);

                        if ($io->isVerbose()) {
                            $io->success("Created new page $newPid for $year/$month");
                            $io->comment("... and $translations translations");
                        }
                    }
                    //override year folder with month folder
                    $pidMap[$record['uid']] = $newPid;
                    $months[$year][$month] = $newPid;
                }
            }
        }

        return $pidMap;
    }

    private function createPage(string $title, int $pid): int
    {
        $archiveRootPage = $this->pageRepository->getPageRecord($this->configuration->getTargetPid());

        $usePageProperties = $this->eventDispatcher->dispatch(
            new CreatePageAttributesEvent(
                ['doktype', 'hidden', 'fe_group', 'perms_userid', 'perms_groupid', 'perms_everybody', 'module', 'backend_layout', 'backend_layout_next_level'],
                $this->configuration
            )
        )->getPageAttributes();

        //use these fields from the archive root page
        $createPage = array_intersect_key(
            $archiveRootPage,
            array_flip($usePageProperties)
        );
        $createPage['title'] = $title;
        $createPage['pid'] = $pid;

        $newId = StringUtility::getUniqueId('NEW');

        $data['pages'][$newId] = $createPage;
        $newPage = $this->runDataHandler($data);

        return $newPage[$newId] ?? 0;
    }

    private function getLanguages(int $pageUid): array
    {
        try {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = $siteFinder->getSiteByPageId($pageUid);

            $languages = $site->getLanguages();
            unset($languages[$site->getDefaultLanguage()->getLanguageId()]);

            return $languages;
        } catch (Throwable) {
            $languages = [];
        }

        return $languages;
    }

    private function translatePage(int $pageUid, array $languages): int
    {
        $translations = 0;

        foreach ($languages as $lang) {
            if ($lang->getLanguageId() === 0) continue;
            $localizeCmd['pages'][$pageUid]['localize'] = $lang->getLanguageId();
            $this->runDataHandler([], $localizeCmd);
            $translations++;
        }

        return $translations;
    }
}

<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Service;

use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use TRAW\NewsArchiver\Domain\Repository\NewsRepository;
use TRAW\NewsArchiver\Domain\Repository\PageRepository;
use TRAW\NewsArchiver\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

final readonly class PageService extends AbstractService
{

    public function __construct(
        protected ConfigurationUtility $configurationUtility,
        protected NewsRepository       $newsRepository,
        protected PageRepository       $pageRepository
    )
    {
    }

    public function getPidMap(array $news, SymfonyStyle $io): array
    {
        $configuration = $this->configurationUtility->getConfiguration();
        if ($configuration->getTargetPid() === 0) {
            throw new \LogicException('Archive target page is missing from the extension\'s settigs');
        }

        $pidMap = [];
        $years = [];
        $months = [];

        $languages = $this->getLanguages($configuration->getTargetPid());

        foreach ($news as $record) {
            if ($configuration->isKeepOriginalStructure()) {
                if ($configuration->createSubFolders()) {

                } else {

                }
            } else {
                if ($configuration->createSubFolders()) {
                    if ($configuration->createYearSubfolders()) {
                        $d = (new \DateTimeImmutable())->setTimestamp($record['datetime']);
                        $year = $d->format('Y');

                        $newPid = $this->pageRepository->findPageByTitleAndPid($year, $configuration->getTargetPid());

                        if ($newPid === 0) {
                            $insertPid = count($years) && isset($years[$year - 1]) ? $years[$year - 1] * -1 : $configuration->getTargetPid();
                            $newPid = $this->createPage($year, $insertPid);
                            $translations = $this->translatePage($newPid, $languages);

                            if ($io->isVerbose()) {
                                $io->success("Created new page $newPid for $year");
                                $io->comment("... and $translations translations");
                            }
                        }

                        $pidMap[$record['uid']] = $newPid;
                        $years[$year] = $newPid;
                    }

                    if ($configuration->createMonthSubfolders()) {
                        $d = (new \DateTimeImmutable())->setTimestamp($record['datetime']);
                        $year = $d->format('Y');
                        $month = $d->format('m');

                        $newPid = $this->pageRepository->findPageByTitleAndPid($month, $years[$year]);

                        if ($newPid === 0) {
                            $previousMonth = sprintf('%02d', (int)$month - 1);
                            $insertPid = isset($months[$year][$previousMonth])
                                ? $months[$year][$previousMonth] * -1
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
                } else {
                    $pidMap[$record['uid']] = $configuration->getTargetPid();
                }
            }
        }

        return $pidMap;
    }

    private function createPage(string $title, int $pid): int
    {
        $archiveRootPage = $this->pageRepository->getPageRecord($this->configurationUtility->getConfiguration()->getTargetPid());

        //use these fields from the archive root page
        $createPage = array_intersect_key(
            $archiveRootPage,
            array_flip(['doktype', 'hidden', 'fe_group', 'perms_userid', 'perms_groupid', 'perms_everybody', 'module', 'backend_layout', 'backend_layout_next_level'])
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
            return [];
        }
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

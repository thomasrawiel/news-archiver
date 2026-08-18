<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Service;

use TRAW\NewsArchiver\Domain\Repository\NewsRepository;
use TRAW\NewsArchiver\Utility\ConfigurationUtility;

class ArchiveService
{
    public function __construct(
        private readonly NewsRepository $newsRepository,
        private ConfigurationUtility    $configurationUtility
    )
    {
    }

    public function archive(): void
    {
        $news = $this->newsRepository->fetchNews($this->configurationUtility->getConfiguration());

        //
    }
}

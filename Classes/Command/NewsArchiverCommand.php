<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use TRAW\NewsArchiver\Domain\DTO\Configuration;
use TRAW\NewsArchiver\Domain\Repository\NewsRepository;
use TRAW\NewsArchiver\Service\ArchiveService;
use TRAW\NewsArchiver\Service\NewsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TRAW\NewsArchiver\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Core\Bootstrap;

#[AsCommand(
    name: 'newsarchiver:run',
    description: 'Archive news records according to the extension\'s settings',
)]
final class NewsArchiverCommand extends Command
{
    public function __construct(
        private readonly ConfigurationUtility $configurationUtility,
        private readonly ArchiveService       $archiveService,
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if($io->isVerbose()) {
            $io->title('Archive news records');
        }

        $settings = $this->configurationUtility->getConfiguration();

        if ($settings->isEnabled() === false) {
            $io->info('News archiver is not enabled. Check the extension\'s settings.');
            return Command::SUCCESS;
        }

        Bootstrap::initializeBackendAuthentication();
        $this->archiveService->archive($io);

        if($io->isVerbose()) {
            $io->success('Archive news records succeeded.');
            $io->info('Running typo3 referenceindex:update is recommended');
        }

        return Command::SUCCESS;
    }
}

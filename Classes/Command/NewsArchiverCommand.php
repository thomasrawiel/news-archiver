<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use TRAW\NewsArchiver\Service\ArchiveService;
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

        $configuration = $this->configurationUtility->getConfiguration();

        if ($configuration->isEnabled() === false) {
            $io->info('News archiver is not enabled. Check the extension\'s settings.');
            return Command::SUCCESS;
        }

        //temp throw exceptions for not yet implemented settings
        if($configuration->isKeepOriginalStructure()) {
            throw new \LogicException('Feature "keepOriginalStructure" is not implemented, yet.');
        }
        if($configuration->getTargetPid() === 0) {
            throw new \LogicException('Feature "targetPid is 0" is not implemented, yet.');
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

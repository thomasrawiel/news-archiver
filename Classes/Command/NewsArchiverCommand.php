<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use TRAW\NewsArchiver\Domain\DTO\Configuration;
use TRAW\NewsArchiver\Service\NewsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TRAW\NewsArchiver\Utility\ConfigurationUtility;

#[AsCommand(
    name: 'newsarchiver:run',
    description: 'Archive news records according to the extension\'s settings',
)]
class NewsArchiverCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly NewsService          $newsService,
        private readonly ConfigurationUtility $configurationUtility,
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->renderTitle();

        $settings = $this->configurationUtility->getConfiguration();

        if ($settings->isEnabled() === false) {
            $this->renderMessage('News archiver is not enabled. Check the extension\'s settings.');
            return Command::SUCCESS;
        }

        $news = $this->newsService->fetchNews($settings);

        return Command::SUCCESS;
    }

    private function renderTitle(): void
    {
        if ($this->io->isVerbose()) {
            $this->io->title('Archive news records');
        }
    }

    private function renderMessage(string $message, bool $veryVerbose = false): void
    {
        if ($veryVerbose && $this->io->isVeryVerbose()) {
            $this->io->writeln($message);
        }
        if (!$veryVerbose && $this->io->isVerbose()) {
            $this->io->writeln($message);
        }
    }
}

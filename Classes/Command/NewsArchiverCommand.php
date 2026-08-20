<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use TRAW\NewsArchiver\Domain\DTO\Configuration;
use TRAW\NewsArchiver\Enum\ArchiveAction;
use TRAW\NewsArchiver\Enum\ArchiveMode;
use TRAW\NewsArchiver\Enum\SubFolders;
use TRAW\NewsArchiver\Service\ArchiveService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TRAW\NewsArchiver\Utility\ConfigurationUtility;
use TRAW\NewsArchiver\Utility\ValidationUtility;
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

    private function getCommandOptions(): array
    {
        return [
            'newsRootFolder' => [
                'shortcut' => 'N',
                'mode' => InputOption::VALUE_OPTIONAL,
                'description' => 'News folder(s):comma-list, leave empty to fetch all news records',
            ],
            'recursive' => [
                'shortcut' => 'r',
                'mode' => InputOption::VALUE_OPTIONAL,
                'description' => 'News folder(s):comma-list, leave empty to fetch all news records',
            ],
            'targetPid' => [
                'shortcut' => 'P',
                'mode' => InputOption::VALUE_OPTIONAL,
                'description' => 'News archive uid:If 0, then the site root will be used and a new Archive folder will be created',
            ],
            'archiveAction' => [
                'shortcut' => 'A',
                'mode' => InputOption::VALUE_OPTIONAL,
                'description' => 'Archive action',
                'suggest' => array_column(ArchiveAction::cases(), 'value'),
            ],
            'archiveMode' => [
                'shortcut' => 'M',
                'mode' => InputOption::VALUE_OPTIONAL,
                'description' => 'Archive mode',
                'suggest' => array_column(ArchiveMode::cases(), 'value'),
            ],
            'archiveNewsAmount' => [
                'shortcut' => 'a',
                'mode' => InputOption::VALUE_OPTIONAL,
                'description' => 'Age in days or amount',
            ],
            'keepOriginalStructure' => [
                'shortcut' => 'O',
                'mode' => InputOption::VALUE_NONE,
                'description' => 'Rebuild the original folder structure in the archive',
            ],
            'subfolders' => [
                'shortcut' => 'S',
                'mode' => InputOption::VALUE_OPTIONAL,
                'description' => 'Subfolders',
                'suggest' => array_column(SubFolders::cases(), 'value'),
            ],
            'limit' => [
                'shortcut' => 'l',
                'mode' => InputOption::VALUE_OPTIONAL,
                'description' => 'Number of news records archived per run',
            ],
            'useDefaultValues' => [
                'shortcut' => null,
                'mode' => InputOption::VALUE_NONE,
                'description' => 'Use extension default values instead of configured settings',
            ],
        ];
    }

    protected function configure(): void
    {
        foreach ($this->getCommandOptions() as $name => $option) {
            $this->addOption($name, $option['shortcut'], $option['mode'], $option['description'], null, $option['suggest'] ?? []);
        }
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($io->isVerbose()) {
            $io->title('Archive news records');
        }

        $configuration = $this->buildConfigurationFromCommandOptions($input);


        if ($configuration->isEnabled() === false) {
            $io->info('News archiver is not enabled. Check the extension\'s settings.');
            return Command::SUCCESS;
        }

        //temp throw exceptions for not yet implemented settings
        if ($configuration->isKeepOriginalStructure()) {
            throw new \LogicException('Feature "keepOriginalStructure" is not implemented, yet.');
        }
        if ($configuration->getTargetPid() === 0) {
            throw new \LogicException('Feature "targetPid is 0" is not implemented, yet.');
        }

        Bootstrap::initializeBackendAuthentication();
        $this->archiveService->archive($io, $configuration);

        if ($io->isVerbose()) {
            $io->success('Archive news records succeeded.');
            $io->info('Running typo3 referenceindex:update is recommended');
        }

        return Command::SUCCESS;
    }

    private function buildConfigurationFromCommandOptions(InputInterface $input): Configuration
    {
        $settings = $this->configurationUtility->getConfiguration();
        $defaultValues = Configuration::DEFAULT_VALUES;

        $cmdOptions = array_fill_keys(array_keys($defaultValues), null);;

        foreach ($cmdOptions as $key => $value) {
            if (!$input->hasOption($key)) continue;
            $cmdOptions[$key] = $input->getOption($key);
        }

        $finalConfiguration = array_replace(
            $input->getOption('useDefaultValues') ? $defaultValues : $settings->__toArray(),
            array_filter($cmdOptions)
        );

        $this->validateConfiguration($finalConfiguration);

        return new Configuration($finalConfiguration);
    }

    private function validateConfiguration(array $configuration): void
    {
        $validationUtility = new ValidationUtility();
        if (!$validationUtility->isPositiveInteger($configuration['archiveNewsAmount'])) {
            throw new \InvalidArgumentException('archiveNewsAmount must be an integer greater than 0');
        }

        if (!$validationUtility->isNonNegativeInteger($configuration['recursive'])) {
            throw new \InvalidArgumentException('recursive must be an integer greater than or equal 0');
        }

        if (!$validationUtility->isPositiveInteger($configuration['limit'])) {
            throw new \InvalidArgumentException('limit must be an integer greater than 0');
        }

        if (!$validationUtility->isIntegerList($configuration['newsRootFolder'])) {
            throw new \InvalidArgumentException('NewsRootFolder must be an integer-commalist or empty');
        }

        if (!$validationUtility->isEnum($configuration['archiveAction'], ArchiveAction::class)) {
            throw new \InvalidArgumentException('ArchiveAction must be one of: ' . implode(', ', array_column(ArchiveAction::cases(), 'value')));
        }
        if (!$validationUtility->isEnum($configuration['archiveMode'], ArchiveMode::class)) {
            throw new \InvalidArgumentException('ArchiveMode must be one of: ' . implode(', ', array_column(ArchiveMode::cases(), 'value')));
        }
        if (!$validationUtility->isEnum($configuration['subfolders'], SubFolders::class)) {
            throw new \InvalidArgumentException('Subfolders must be one of: ' . implode(', ', array_column(SubFolders::cases(), 'value')));
        }
    }
}

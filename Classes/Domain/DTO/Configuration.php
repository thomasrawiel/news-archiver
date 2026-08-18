<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Domain\DTO;

use TRAW\NewsArchiver\Enum\ArchiveAction;
use TRAW\NewsArchiver\Enum\ArchiveMode;
use TRAW\NewsArchiver\Enum\SubFolders;
use TYPO3\CMS\Core\SingletonInterface;
use function Symfony\Component\String\s;

class Configuration implements SingletonInterface
{
    private const array DEFAULT_VALUES = [
        'enable' => false,
        'archiveAction' => ArchiveAction::BOTH->value,
        'archiveMode' => ArchiveMode::AGE->value,
        'archiveNewsAmount' => 90,
        'subfolders' => SubFolders::NONE->value,
        'recursive' => 0,
    ];
    private bool $isEnabled;
    private ArchiveAction $archiveAction;
    private ArchiveMode $archiveMode;
    private int $archiveNewsAmount;
    private SubFolders $subfolders;
    private string $newsRootFolder;
    private int $recursive;
    private int $targetPid;


    public function __construct(array $configuration)
    {
        $this->isEnabled = (bool)($configuration['enable'] ?? self::DEFAULT_VALUES['enable']);
        $this->archiveNewsAmount = (int)($configuration['archiveNewsAmount'] ?? self::DEFAULT_VALUES['archiveNewsAmount']);
        $this->newsRootFolder = (string)($configuration['newsRootFolder'] ?? '');
        $this->recursive = (int)($configuration['recursive'] ?? self::DEFAULT_VALUES['recursive']);
        $this->targetPid = (int)($configuration['targetPid'] ?? 0);

        $this->archiveAction = ArchiveAction::tryFrom((string)$configuration['archiveAction']) ?? ArchiveAction::from(self::DEFAULT_VALUES['archiveAction']);;
        $this->archiveMode = ArchiveMode::tryFrom((string)$configuration['archiveMode']) ?? ArchiveMode::from(self::DEFAULT_VALUES['archiveMode']);
        $this->subfolders = SubFolders::tryFrom((string)$configuration['subfolders']) ?? SubFolders::from(self::DEFAULT_VALUES['subfolders']);
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getArchiveAction(): ArchiveAction
    {
        return $this->archiveAction;
    }

    public function isArchiveModeMove(): bool
    {
        return $this->archiveAction->allowsMove();
    }

    public function isArchiveModeArchive(): bool
    {
        return $this->archiveAction->allowsArchive();
    }

    public function getArchiveMode(): ArchiveMode
    {
        return $this->archiveMode;
    }

    public function archiveNewsByAge(): bool
    {
        return $this->archiveMode === ArchiveMode::AGE;
    }

    public function archiveNewsByAmount(): bool
    {
        return $this->archiveMode === ArchiveMode::AMOUNT;
    }

    public function archiveAllNews(): bool {
        return $this->archiveMode === ArchiveMode::ALL;
    }

    public function getArchiveNewsAmount(): int
    {
        return $this->archiveNewsAmount;
    }

    public function getSubfolders(): SubFolders
    {
        return $this->subfolders;
    }

    public function createSubFolders(): bool
    {
        return $this->subfolders->allowSubFolders();
    }

    public function createYearSubfolders(): bool
    {
        return $this->subfolders->allowYear();
    }

    public function createMonthSubfolders(): bool
    {
        return $this->subfolders->allowMonth();
    }

    public function getNewsRootFolder(): string
    {
        return $this->newsRootFolder;
    }

    public function ignorePids(): bool {
        return $this->newsRootFolder === '';
    }

    public function getRecursive(): int
    {
        return $this->recursive;
    }

    public function getTargetPid(): int
    {
        return $this->targetPid;
    }
}

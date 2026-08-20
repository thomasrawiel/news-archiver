<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Domain\DTO;

use BackedEnum;
use TRAW\NewsArchiver\Enum\ArchiveAction;
use TRAW\NewsArchiver\Enum\ArchiveMode;
use TRAW\NewsArchiver\Enum\SubFolders;
use TYPO3\CMS\Core\SingletonInterface;

class Configuration implements SingletonInterface
{
    public const array DEFAULT_VALUES = [
        'enable' => true,
        'archiveAction' => ArchiveAction::BOTH->value,
        'archiveMode' => ArchiveMode::AGE->value,
        'archiveNewsAmount' => 365,
        'newsRootFolder' => '',
        'keepOriginalStructure' => false,
        'subfolders' => SubFolders::NONE->value,
        'recursive' => 0,
        'limit' => 200,
        'targetPid' => 0,
    ];
    private bool $isEnabled;
    private string $newsRootFolder;
    private int $recursive;
    private int $targetPid;
    private int $archiveNewsAmount;
    private bool $keepOriginalStructure;
    private int $limit;
    private ArchiveAction $archiveAction;
    private ArchiveMode $archiveMode;
    private SubFolders $subfolders;


    public function __construct(array $configuration)
    {
        $this->isEnabled = (bool)$this->resolveSetting('enable', $configuration);
        $this->archiveNewsAmount = (int)$this->resolveSetting('archiveNewsAmount', $configuration);
        $this->newsRootFolder = (string)$this->resolveSetting('newsRootFolder', $configuration);
        $this->recursive = (int)$this->resolveSetting('recursive', $configuration);
        $this->targetPid = (int)$this->resolveSetting('targetPid', $configuration);
        $this->keepOriginalStructure = (bool)$this->resolveSetting('keepOriginalStructure', $configuration);
        $this->limit = (int)$this->resolveSetting('limit', $configuration);

        $this->archiveAction = $this->resolveEnum(ArchiveAction::class, 'archiveAction', $configuration);
        $this->archiveMode = $this->resolveEnum(ArchiveMode::class, 'archiveMode', $configuration);
        $this->subfolders = $this->resolveEnum(SubFolders::class, 'subfolders', $configuration);
    }

    /**
     * @template NewsArchiverEnum of BackedEnum
     *
     * @param class-string<NewsArchiverEnum> $enumClass
     * @param string                         $setting
     * @param array                          $configuration
     *
     * @return NewsArchiverEnum
     */
    private function resolveEnum(string $enumClass, string $setting, array $configuration): BackedEnum
    {
        $value = $this->resolveSetting($setting, $configuration);

        if ($value instanceof $enumClass) {
            return $value;
        }

        return $enumClass::tryFrom((string)$value)
            ?? $enumClass::from(self::DEFAULT_VALUES[$setting]);
    }

    /**
     * @template NewsArchiverEnum of BackedEnum
     *
     * @param string $setting
     * @param array  $configuration
     *
     * @return string|int|bool|NewsArchiverEnum
     */
    private function resolveSetting(string $setting, array $configuration): string|int|bool|BackedEnum    {
        return $configuration[$setting] ?? self::DEFAULT_VALUES[$setting];
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

    public function archiveAllNews(): bool
    {
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

    public function ignorePids(): bool
    {
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

    public function isKeepOriginalStructure(): bool
    {
        return $this->keepOriginalStructure;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function __toArray(): array
    {
        return [
            'isEnabled' => $this->isEnabled,
            'archiveNewsAmount' => $this->archiveNewsAmount,
            'newsRootFolder' => $this->newsRootFolder,
            'recursive' => $this->recursive,
            'targetPid' => $this->targetPid,
            'keepOriginalStructure' => $this->keepOriginalStructure,
            'limit' => $this->limit,
            'archiveAction' => $this->archiveAction,
            'archiveMode' => $this->archiveMode,
            'subfolders' => $this->subfolders,
        ];
    }
}

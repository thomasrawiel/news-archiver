<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Utility;

use TRAW\NewsArchiver\Domain\DTO\Configuration;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ConfigurationUtility implements SingletonInterface
{
    private ?Configuration $configuration = null;

    public function __construct()
    {
        try {
            $conf = GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get('news_archiver');
            $this->configuration = GeneralUtility::makeInstance(Configuration::class, $conf);
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }
}

<?php
declare(strict_types=1);

namespace TRAW\NewsArchiver\Service;

use TRAW\NewsArchiver\Domain\DTO\Configuration;
use TYPO3\CMS\Core\Database\ConnectionPool;

class NewsService
{
    public function __construct(private readonly ConnectionPool $connectionPool)
    {
    }

    public function fetchNews(Configuration $configuration): array
    {
        if(!$configuration->isEnabled()) {
            return [];
        }



        $qb = $this->connectionPool->getQueryBuilderForTable('tx_news_domain_model_news');
        $expr = $qb->expr();


    }
}

<?php

declare(strict_types=1);

namespace In2code\In2studyfinder\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

class AbstractUtility
{
    public static function getConfigurationManager(): ConfigurationManager
    {
        return GeneralUtility::makeInstance(ConfigurationManager::class);
    }

    public static function getExtensionConfiguration(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['in2studyfinder'];
    }

    protected static function getQueryBuilderForTable(string $table): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }
}

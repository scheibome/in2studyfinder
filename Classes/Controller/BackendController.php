<?php

declare(strict_types=1);

namespace In2code\In2studyfinder\Controller;

use In2code\In2studyfinder\Domain\Model\StudyCourse;
use In2code\In2studyfinder\Domain\Repository\StudyCourseRepository;
use In2code\In2studyfinder\Service\ExportService;
use In2code\In2studyfinder\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * StudyCourseController
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class BackendController extends AbstractController
{
    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var StudyCourseRepository
     */
    protected $studyCourseRepository;

    public function initializeAction()
    {
        parent::initializeAction();

        $this->reflectionService = GeneralUtility::makeInstance(ReflectionService::class);
        $this->studyCourseRepository = $this->setStudyCourseRepository();
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function listAction(): void
    {
        $this->validateSettings();

        $recordLanguage = 0;

        if ($this->request->hasArgument('recordLanguage')) {
            $recordLanguage = (int)$this->request->getArgument('recordLanguage');
        }

        $studyCourses = $this->getStudyCoursesForExportList($recordLanguage);

        $possibleExportDataProvider = $this->getPossibleExportDataProvider();
        $propertyArray = [];

        if ($studyCourses !== null && empty($studyCourses)) {
            $this->addFlashMessage(
                LocalizationUtility::translate('messages.noCourses.body', 'in2studyfinder'),
                LocalizationUtility::translate('messages.noCourses.title', 'in2studyfinder'),
                AbstractMessage::WARNING
            );
        } else {
            $this->getFullPropertyList(
                $propertyArray,
                $this->reflectionService->getClassSchema(
                    $this->studyCourseRepository->findOneByDeleted(0)
                )->getProperties()
            );
        }

        $itemsPerPage = $this->settings['pagination']['itemsPerPage'];

        if ($this->request->hasArgument('itemsPerPage')) {
            $itemsPerPage = $this->request->getArgument('itemsPerPage');
        }

        $this->view->assignMultiple(
            [
                'studyCourses' => $studyCourses,
                'exportDataProvider' => $possibleExportDataProvider,
                'availableFieldsForExport' => $propertyArray,
                'sysLanguages' => $this->getSysLanguages(),
                'itemsPerPage' => $itemsPerPage
            ]
        );
    }

    protected function getSysLanguages(): array
    {
        $sysLanguages = [
            0 => 'default'
        ];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $languageRecords = $queryBuilder
            ->select('*')
            ->from('sys_language')
            ->where($queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)))
            ->orderBy('sorting')
            ->execute()->fetchAll();

        foreach ($languageRecords as $languageRecord) {
            $sysLanguages[(int)$languageRecord['uid']] =
                LocalizationUtility::translate(
                    'LLL:EXT:core/Resources/Private/Language/db.xlf:sys_language.language_isocode.' .
                    $languageRecord['language_isocode']
                );
        }

        return $sysLanguages;
    }

    /**
     * @param int $languageUid
     * @return array|null
     */
    protected function getStudyCoursesForExportList(int $languageUid = 0)
    {
        $queryBuilder =
            GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(StudyCourse::TABLE);
        $includeDeleted = (int)$this->settings['backend']['export']['includeDeleted'];
        $includeHidden = (int)$this->settings['backend']['export']['includeHidden'];
        $storagePid = (int)$this->settings['storagePid'];

        if ($includeDeleted) {
            $queryBuilder->getRestrictions()->removeByType(DeletedRestriction::class);
        }

        if ($includeHidden) {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $records = $queryBuilder
            ->select('uid', 'l10n_parent', 'title', 'hidden', 'deleted')
            ->from(StudyCourse::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($storagePid, \PDO::PARAM_INT))
            )
            ->orderBy('title')
            ->execute()->fetchAll();

        return $records;
    }

    /**
     * @return array
     */
    public function getPossibleExportDataProvider(): array
    {
        $possibleDataProvider = [];

        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['in2studyfinder']['exportTypes'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['in2studyfinder']['exportTypes'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['in2studyfinder']['exportTypes'] as $providerName => $providerClass) {
                if (!class_exists($providerClass)) {
                    $this->addFlashMessage(
                        'export provider class "' . $providerClass . '" was not found',
                        'export provider class not found',
                        AbstractMessage::ERROR
                    );
                } else {
                    $possibleDataProvider[$providerName] = $providerClass;
                }
            }
        }

        return $possibleDataProvider;
    }

    /**
     * @param string $exporter
     * @param integer $recordLanguage
     * @param array $selectedProperties
     * @param array $courseList
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function exportAction(
        string $exporter,
        int $recordLanguage,
        array $selectedProperties,
        array $courseList
    ): void {
        if (empty($selectedProperties) || empty($courseList)) {
            $this->addFlashMessage(
                LocalizationUtility::translate('messages.notAllRequiredFieldsSet.body', 'in2studyfinder'),
                LocalizationUtility::translate('messages.notAllRequiredFieldsSet.title', 'in2studyfinder'),
                AbstractMessage::ERROR
            );

            $this->forward('list');
        }

        $courses = $this->studyCourseRepository->findByUidsAndLanguage($courseList, (int)$recordLanguage);

        $exportService =
            GeneralUtility::makeInstance(ExportService::class, $exporter, $selectedProperties, $courses->toArray());

        $exportService->export();
    }

    /**
     * @param $propertyArray
     * @param $objectProperties
     */
    protected function getFullPropertyList(
        &$propertyArray,
        $objectProperties
    ): void {
        foreach ($objectProperties as $propertyName => $propertyInformation) {
            if (!in_array($propertyName, $this->settings['backend']['export']['excludedPropertiesForExport'])) {
                $elementType = $propertyInformation->getElementType();
                $type = $propertyInformation->getType();

                if ($type === ObjectStorage::class) {
                    if (class_exists($elementType)) {
                        $this->getFullPropertyList(
                            $propertyArray[$propertyName],
                            $this->reflectionService->getClassSchema($elementType)
                                ->getProperties()
                        );
                    }
                } else {
                    if (class_exists($type)) {
                        $this->getFullPropertyList(
                            $propertyArray[$propertyName],
                            $this->reflectionService->getClassSchema($type)->getProperties()
                        );
                    } else {
                        $propertyArray[$propertyName] = $propertyName;
                    }
                }
            }
        }
    }

    /**
     * set the studyCourseRepository
     */
    protected function setStudyCourseRepository()
    {
        $extendedRepositoryName = 'In2code\\In2studyfinderExtend\\Domain\\Repository\\StudyCourseRepository';

        if (ExtensionUtility::isIn2studycoursesExtendLoaded()
            && class_exists($extendedRepositoryName)) {
            return GeneralUtility::makeInstance($extendedRepositoryName);
        }

        return GeneralUtility::makeInstance(StudyCourseRepository::class);
    }

    /**
     * @return void
     */
    protected function validateSettings(): void
    {
        if (!isset($this->settings['storagePid']) || empty($this->settings['storagePid'])) {
            $this->addFlashMessage(
                LocalizationUtility::translate('messages.noStoragePid.body', 'in2studyfinder'),
                LocalizationUtility::translate('messages.noStoragePid.title', 'in2studyfinder'),
                AbstractMessage::ERROR
            );
        }

        if (!isset($this->settings['settingsPid']) || empty($this->settings['settingsPid'])) {
            $this->addFlashMessage(
                LocalizationUtility::translate('messages.noSettingsPid.body', 'in2studyfinder'),
                LocalizationUtility::translate('messages.noSettingsPid.title', 'in2studyfinder'),
                AbstractMessage::ERROR
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace In2code\In2studyfinder\Controller;

use In2code\In2studyfinder\Domain\Model\StudyCourse;
use In2code\In2studyfinder\Domain\Service\CourseService;
use In2code\In2studyfinder\Domain\Service\FacilityService;
use In2code\In2studyfinder\Service\FilterService;
use In2code\In2studyfinder\Utility\CacheUtility;
use In2code\In2studyfinder\Utility\ConfigurationUtility;
use In2code\In2studyfinder\Utility\FlexFormUtility;
use In2code\In2studyfinder\Utility\FrontendUtility;
use In2code\In2studyfinder\Utility\RecordUtility;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class StudyCourseController extends AbstractController
{

    protected FilterService $filterService;

    protected CourseService $courseService;

    protected FacilityService $facilityService;

    public function __construct(
        FilterService $filterService,
        CourseService $courseService,
        FacilityService $facilityService
    ) {
        $this->filterService = $filterService;
        $this->courseService = $courseService;
        $this->facilityService = $facilityService;
    }

    /**
     * Strip empty options from incoming (selected) filters
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function initializeFilterAction(): void
    {
        $this->filterService->initialize();

        if ($this->request->hasArgument('searchOptions')) {
            $searchOptions = array_filter((array)$this->request->getArgument('searchOptions'));
            $this->request->setArgument('searchOptions', $searchOptions);

            if (ConfigurationUtility::isPersistentFilterEnabled()) {
                FrontendUtility::getTyposcriptFrontendController()
                    ->fe_user
                    ->setAndSaveSessionData('tx_in2studycourse_filter', $searchOptions);
            }
        } else {
            if (ConfigurationUtility::isPersistentFilterEnabled()) {
                $this->request->setArgument(
                    'searchOptions',
                    FrontendUtility::getTyposcriptFrontendController()
                        ->fe_user
                        ->getSessionData('tx_in2studycourse_filter')
                );
            }
        }
    }

    /**
     * @param array $searchOptions
     * @param array $pluginInformation contains additional plugin information from ajax / fetch requests
     * @return void
     */
    public function filterAction(array $searchOptions = [], array $pluginInformation = []): void
    {
        if (!empty($pluginInformation)) {
            // if the current call is an ajax / fetch request
            $currentPluginRecord =
                RecordUtility::getRecordWithLanguageOverlay(
                    (int)$pluginInformation['pluginUid'],
                    (int)$pluginInformation['languageUid']
                );

            $this->settings =
                array_merge(
                    $this->settings,
                    FlexFormUtility::getFlexForm($currentPluginRecord['pi_flexform'], 'settings')
                );
        } else {
            $currentPluginRecord = $this->configurationManager->getContentObject()->data;
        }

        $studyCourses = $this->courseService->findBySearchOptions(
            $this->filterService->setSettings($this->settings)->prepareSearchOptions($searchOptions),
            $currentPluginRecord
        );

        $this->view->assignMultiple(
            [
                'searchedOptions' => $searchOptions,
                'filters' => $this->filterService->getFilter(),
                'availableFilterOptions' => $this->filterService->getAvailableFilterOptions($studyCourses),
                'studyCourseCount' => count($studyCourses),
                'studyCourses' => $studyCourses,
                'settings' => $this->settings,
                'data' => $currentPluginRecord
            ]
        );
    }

    /**
     * fastSearchAction
     */
    public function fastSearchAction(): void
    {
        $studyCourses =
            $this->courseService->findBySearchOptions([], $this->configurationManager->getContentObject()->data);

        $this->view->assignMultiple(
            [
                'studyCourseCount' => count($studyCourses),
                'facultyCount' => $this->facilityService->getFacultyCount($this->settings),
                'studyCourses' => $studyCourses,
                'settings' => $this->settings
            ]
        );
    }


    /**
     * @param StudyCourse|null $studyCourse
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function detailAction(StudyCourse $studyCourse = null): void
    {
        if ($studyCourse) {
            $this->courseService->setPageTitleAndMetadata($studyCourse);
            CacheUtility::addCacheTags([$studyCourse]);

            $this->view->assign('studyCourse', $studyCourse);
        } else {
            $this->redirect('filterAction', null, null, null, $this->settings['flexform']['studyCourseListPage']);
        }
    }
}

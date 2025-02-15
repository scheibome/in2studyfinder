<?php

declare(strict_types=1);

namespace In2code\In2studyfinder\ViewHelpers\Form;

use In2code\In2studyfinder\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class SelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument(
            'additionalOptionAttributes',
            'array',
            'Array which holds "propertyType" => array("uid", ...)" for each option'
        );

        $this->registerArgument(
            'detailPageUid',
            'string',
            'the uid of the of the detail page'
        );

        $this->registerArgument(
            'action',
            'string',
            'the target action of the select (fallback is detail)'
        );
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getOptions(): array
    {
        $parentOptions = parent::getOptions();
        $options = [];
        $optionsArgument = $this->arguments['options'];
        $settings = ExtensionUtility::getExtensionSettings('in2studyfinder');
        $pageUid = $settings['flexform']['studyCourseDetailPage'];

        if ($this->hasArgument('action')) {
            $action = $this->arguments['action'];
        } else {
            $action = 'detail';
        }

        if ($this->hasArgument('detailPageUid')) {
            $pageUid = $this->arguments['detailPageUid'];
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        foreach ($optionsArgument as $value) {
            if ($this->hasArgument('additionalOptionAttributes')) {
                if ($value instanceof AbstractDomainObject) {
                    $optionsArrayKey = get_class($value) . ':' . $value->getUid();
                    $label = '';

                    if (array_key_exists($optionsArrayKey, $parentOptions)) {
                        $label = $parentOptions[$optionsArrayKey];
                    }

                    $options[$optionsArrayKey]['label'] = $label;

                    foreach ($this->arguments['additionalOptionAttributes'] as $attribute => $property) {
                        $options[$optionsArrayKey]['additionalAttributes'][$attribute] =
                            \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath(
                                $value,
                                $property
                            );

                        $uri =
                            $uriBuilder->reset()->setRequest($this->getRequest());

                        if (!empty($pageUid)) {
                            $uri->setTargetPageUid((int)$pageUid);
                        }

                        $uri->uriFor(
                            $action,
                            ['studyCourse' => $value]
                        );

                        $options[$optionsArrayKey]['additionalAttributes']['data-url'] = $uri->build();
                    }
                }
            }
        }

        return $options;
    }

    protected function renderOptionTags($options): string
    {
        $output = '';

        // options from option attribute
        foreach ($options as $value => $attributes) {
            if ('' === $value && empty($attributes)) {
                continue;
            }
            $isSelected = $this->isSelected($value);

            $additionalAttributes = [];
            if (!empty($attributes['additionalAttributes'])) {
                $additionalAttributes = $attributes['additionalAttributes'];
            }

            $output .= $this->renderOptionTag($value, $attributes['label'], $isSelected, $additionalAttributes) . LF;
        }

        return $output;
    }

    /**
     * Render one option tag
     *
     * @param string $value value attribute of the option tag (will be escaped)
     * @param string $label content of the option tag (will be escaped)
     * @param bool $isSelected specifies wheter or not to add selected attribute
     * @param array $additionalAttributes array with additional attributes
     * @return string the rendered option tag
     */
    protected function renderOptionTag($value, $label, $isSelected, $additionalAttributes = [])
    {
        $output =
            '<option value="' . htmlspecialchars($value) . '" ' . $this->getAdditionalAttributesString(
                $additionalAttributes
            );
        if ($isSelected) {
            $output .= ' selected="selected"';
        }
        $output .= '>' . htmlspecialchars($label) . '</option>';
        return $output;
    }

    /**
     * @param array $additionalAttributes
     * @return string
     */
    protected function getAdditionalAttributesString($additionalAttributes)
    {
        $output = '';

        if (!empty($additionalAttributes)) {
            foreach ($additionalAttributes as $attribute => $value) {
                $output .= ' ' . htmlspecialchars($attribute) . '="' . htmlspecialchars($value) . '"';
            }
        }

        return ltrim($output);
    }
}

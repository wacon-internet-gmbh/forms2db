<?php

/**
 * This file is part of the "forms2db" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace WACON\Forms2db\Domain\Finishers;

use WACON\Forms2db\Domain\Model\Mail;
use WACON\Forms2db\Domain\Repository\MailRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;

/**
 * Class Forms2dbFinisher
 *
 */
class Forms2dbFinisher extends AbstractFinisher
{

    /**
     * Dont save this fields in database (also used in FromResultsController)
     */
    public const EXCLUDE_FIELDS = ['Honeypot', 'StaticText', 'ContentElement', 'GridRow', 'SummaryPage'];

    /**
     * The formDefinition
     *
     * @var FormDefinition
     */
    protected $formDefinition;

    /**
     * The MailRepository
     *
     * @var MailRepository
     */
    protected $mailRepository;

    /**
     * The ConfigurationManagerInterface
     *
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * Injects the MailRepository
     *
     * @param MailRepository $mailRepository
     */
    public function injectMailRepository(MailRepository $mailRepository): void
    {
        $this->mailRepository = $mailRepository;
    }

    /**
     * Injects the ConfigurationManagerInterface
     *
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * getFormFieldValues
     *
     * Recurrsive method to get all form field values including nested ones
     *
     * @param  array $fields
     * @param  array $nestedIdentifier Array of levels nested - populated during recursion
     * @return array
     */
    private function getFormFieldValues(array $fields, $nestedIdentifier = []): array
    {
        $formValues = [];

        foreach ($fields as $fieldName => $fieldValue) {
            $newNestedIdentifier = $nestedIdentifier;

            // Are we a valid field or a repeatable container?
            $isValidField = !is_null($this->formDefinition->getElementByIdentifier($fieldName));

            if (is_array($fieldValue) && !$isValidField) {
                $newNestedIdentifier[] = $fieldName;
                $formValues = array_merge($this->getFormFieldValues($fieldValue, $newNestedIdentifier), $formValues);
            } else {
                if(count($nestedIdentifier)) {
                    $fieldNameIdentifier = array_merge($nestedIdentifier, [$fieldName]);
                    $fieldName = implode('.', $fieldNameIdentifier);
                }

                // Get the field with the new constructed name
                $fieldElement = $this->formDefinition->getElementByIdentifier($fieldName);

                if (
                    $fieldElement instanceof FormElementInterface &&
                    in_array(
                        $fieldElement->getType(),
                        self::EXCLUDE_FIELDS,
                        true
                    ) === false
                ) {
                    if ($fieldValue instanceof FileReference) {
                        $formValues[$fieldName] = $fieldValue->getOriginalResource()->getCombinedIdentifier();
                    } else {
                        $formValues[$fieldName] = $fieldValue;
                    }
                }
            }
        }

        return $formValues;
    }

    /**
     * Writes the form-result into the database, excludes Honeypot
     *
     * @throws IllegalObjectTypeException
     */
    protected function executeInternal(): void
    {
        $this->formDefinition = $this->finisherContext->getFormRuntime()->getFormDefinition();
        if ($this->formDefinition instanceof FormDefinition) {
            /** @noinspection PhpInternalEntityUsedInspection */
            $formPersistenceIdentifier = $this->formDefinition->getPersistenceIdentifier();

            $formValues = $this->getFormFieldValues($this->finisherContext->getFormValues());

            $delimiter = strrpos($this->formDefinition->getIdentifier(), '-');
            $formPluginUid = substr($this->formDefinition->getIdentifier(), $delimiter + 1);
            $formIdentifier = substr($this->formDefinition->getIdentifier(), 0, $delimiter);
            $mail = GeneralUtility::makeInstance(Mail::class);
            $mail->setPersistenceId($formPersistenceIdentifier);
            $mail->setSiteId($GLOBALS['TYPO3_REQUEST']->getAttribute('site')->getIdentifier());
            $mail->setPid($GLOBALS['TSFE']->id);
            $mail->setMailFromArray($formValues);
            $mail->setPluginId($formPluginUid);
            $mail->setFormId($formIdentifier);

            $this->mailRepository->add($mail);
            $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
            $persistenceManager->persistAll();

            $this->finisherContext->getFinisherVariableProvider()->add(
                $this->shortFinisherIdentifier,
                'forms2db.mail',
                $mail
            );
        }
    }
}

<?php
/**
 * This file is part of the "forms2db" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace WACON\Forms2db\Domain\Model;

use DateTime;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

/**
 * Class Mail
 *
 * @package Wacon\Forms2db\Domain\Model
 */
class Mail extends AbstractEntity
{

    /**
     * The formPersistenceIdentifier
     *
     * @see FormDefinition->persistenceIdentifier
     */
    protected string $persistenceId = '';

    /**
     * Unique form identifier
     *
     * @see config/sites/{identifier}/config.yaml
     */
    protected string $formId = '';


    /**
     * Uid of the form plugin content element
     *
     * @var integer
     */
    protected $pluginId = 0;

    /**
     * The form result as json
     */
    protected string $mail = '';

 
 /** @var int */
protected $crdate;

    /**
     * Timestamp
     */
    protected \DateTime $tstamp;

    /**
     * Gets the persistenceId
     *
     * @return string
     */
    public function getPersistenceId(): string
    {
        return $this->persistenceId;
    }

    /**
     * Sets the persistenceId
     *
     * @param string $persistenceId
     */
    public function setPersistenceId(string $persistenceId): void
    {
        $this->persistenceId = $persistenceId;
    }

    /**
     * Gets the formId
     *
     * @return string
     */
    public function getFormId(): string
    {
        return $this->formId;
    }

    /**
     * @param string $formId
     */
    public function setFormId(string $formId): void
    {
        $this->formId = $formId;
    }



    /**
     * Gets the pluginId
     *
     * @return int
     */
    public function getPluginId(): int
    {
        return $this->pluginId;
    }

    /**
     * Sets the pluginId
     *
     * @param int $pluginId
     */
    public function setPluginId(int $pluginId): void
    {
        $this->pluginId = $pluginId;
    }

    /**
     * Gets the mail
     *
     * @return string
     */
    public function getMail(): string
    {
        return $this->mail;
    }

    /**
     * Gets the mail as an array
     *
     * @return array
     */
    public function getMailAsArray(): array
    {
        return $this->mail !== '' ? json_decode($this->mail, true, 512, JSON_THROW_ON_ERROR) : [];
    }

    /**
     * Sets the mail
     *
     * @param string $mail
     */
    public function setMail(string $mail): void
    {
        $this->mail = $mail;
    }

    /**
     * Sets the mail from an array
     *
     * @param array $mailArray
     */
    public function setMailFromArray(array $mailArray): void
    {
        $this->setMail(!empty($mailArray) ? json_encode($mailArray, JSON_THROW_ON_ERROR) : '');
    }

   /**
    * Returns the crdate
    *
    * @return int
    */
    public function getCrdate() {
        return $this->crdate;   
    }

    /**
     * Sets the crdate
     *
     * @param int $crdate
     */
    public function setCrdate(int $crdate): void
    {
        $this->crdate = $crdate;
    }

    /**
     * Gets the tstamp
     *
     * @return DateTime
     */
    public function getTstamp(): DateTime
    {
        return $this->tstamp;
    }

    /**
     * Sets the tstamp
     *
     * @param DateTime $tstamp
     */
    public function setTstamp(DateTime $tstamp): void
    {
        $this->tstamp = $tstamp;
    }
}

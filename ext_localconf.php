<?php
/**
 * This file is part of the "forms2db" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */


defined('TYPO3') or die();
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
call_user_func(function (): void {
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'actions-print',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:forms2db/Resources/Public/Icons/Extension.svg']
    );

});
ExtensionManagementUtility::addTypoScriptSetup('
    module.tx_form.settings.yamlConfigurations {
     1560425499 = EXT:forms2db/Configuration/Yaml/BaseSetup.yaml
	 1560425500 = EXT:forms2db/Configuration/Yaml/FormEditorSetup.yaml
    }
    plugin.tx_form.settings.yamlConfigurations {
     1560425499 = EXT:forms2db/Configuration/Yaml/BaseSetup.yaml
	 1560425500 = EXT:forms2db/Configuration/Yaml/FormEditorSetup.yaml
    }
');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['tx_forms2db_domain_model_mail'] = [
    'dateField' => 'tstamp',
    'expirePeriod' => '180',
 ];


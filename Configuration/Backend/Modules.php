<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */


use WACON\Forms2db\Controller\Backend\FormsdbModuleController;

/**
 * Definitions for modules provided by EXT:examples
 */
return [
    'web_forms2db' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/page/form2db',
        'labels' => 'LLL:EXT:forms2db/Resources/Private/Language/Module/locallang_mod.xlf',
        'extensionName' => 'forms2db',
        'iconIdentifier' => 'actions-print',
        'controllerActions' => [
            FormsdbModuleController::class => [
                'list','show','excel','deleteok','delete',
            ],
        ],
    ],
 
];
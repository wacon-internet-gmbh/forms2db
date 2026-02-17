<?php
/**
 * This file is part of the "forms2db" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */


/** @noinspection PhpUndefinedVariableInspection */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Forms finisher: save to database',
    'description' => 'With forms2db, form data from the Forms extension is stored in the database. Additionally, the data can be exported in CSV format using a dedicated module.',
    'category' => 'frontend',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.4.99',
            'form' => '13.4.0-14.4.99'
        ],
        'conflicts' => [],
        'suggests' => []
    ],
    'autoload' => [
        'psr-4' => [
            'WACON\\Forms2db\\' => 'Classes'
        ],
    ],
    'state' => 'stable',
    'author' => 'Kerstin Schmitt',
    'author_email' => 'info@wacon.de',
    'author_company' => 'WACON Internet GmbH',
    'version' => '2.0.1'
];

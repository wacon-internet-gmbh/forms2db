<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:forms2db/Resources/Private/Language/locallang_db.xlf:tx_forms2db_domain_model_mail',
        'label' => 'form_id',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'persistence_id,form_id,plugin_id,mail',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'iconfile' => 'EXT:forms2db/Resources/Public/Icons/Extension.png'
    ],
    'types' => [
        '1' => ['showitem' => 'persistence_id, form_id,plugin_id,mail, crdate, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime'],
    ],
    'columns' => [
        'persistence_id' => [
            'label' => 'LLL:EXT:forms2db/Resources/Private/Language/locallang_db.xlf:tx_forms2db_domain_model_mail.persistence_id',
            'config' => [
                'type' => 'input',
                'required' => true
            ]
        ],
        'form_id' => [
            'label' => 'LLL:EXT:forms2db/Resources/Private/Language/locallang_db.xlf:tx_forms2db_domain_model_mail.form_id',
            'config' => [
                'type' => 'input',
                'required' => true
            ]
        ],
        'plugin_id' => [
            'label' => 'LLL:EXT:forms2db/Resources/Private/Language/locallang_db.xlf:tx_forms2db_domain_model_mail.plugin_id',
            'config' => [
                'type' => 'input',
                'required' => true
            ]
        ],
        'crdate' => [
            'label' => 'Date',
            'config' => [
                'type' => 'datetime',
                'required' => true
            ]
        ],
        'mail' => [
            'label' => 'LLL:EXT:forms2db/Resources/Private/Language/locallang_db.xlf:tx_forms2db_domain_model_mail.result',
            'config' => [
                'type' => 'text',
                'cols' => 20,
                'rows' => 5,
            ],
        ]
    ]
];

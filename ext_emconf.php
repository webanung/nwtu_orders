<?php

/**
 * Extension Manager/Repository config file for ext "schminckede_farbkarte".
 */
$EM_CONF[$_EXTKEY] = [
    'title' => 'NWTU Bestellungen',
    'description' => '',
    'category' => 'plugin',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'fluid_styled_content' => '11.5.0-11.5.99',
            'rte_ckeditor' => '11.5.0-11.5.99',
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'WebanUg\\NwtuOrders\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Lars Tiefland',
    'author_email' => 'l.tiefland@weban.de',
    'author_company' => 'Weban UG',
    'version' => '1.0.0',
];

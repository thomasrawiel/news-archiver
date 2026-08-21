<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'News Archiver',
    'description' => 'Move news to archive folders',
    'category' => 'misc',
    'author' => 'Thomas Rawiel',
    'author_email' => 'thomas.rawiel@gmail.com',
    'state' => 'beta',
    'version' => '0.2.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.20-14.4.99',
            'news' => '',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];

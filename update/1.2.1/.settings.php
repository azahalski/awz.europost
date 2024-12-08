<?php
return [
    'controllers' => [
        'value' => [
            'namespaces' => [
                '\\Awz\\Europost\\Api\\Controller' => 'api'
            ]
        ],
        'readonly' => true
    ],
    'ui.entity-selector' => [
        'value' => [
            'entities' => [
                [
                    'entityId' => 'awzeuropost-user',
                    'provider' => [
                        'moduleId' => 'awz.europost',
                        'className' => '\\Awz\\Europost\\Access\\EntitySelectors\\User'
                    ],
                ],
                [
                    'entityId' => 'awzeuropost-group',
                    'provider' => [
                        'moduleId' => 'awz.europost',
                        'className' => '\\Awz\\Europost\\Access\\EntitySelectors\\Group'
                    ],
                ],
            ]
        ],
        'readonly' => true,
    ]
];
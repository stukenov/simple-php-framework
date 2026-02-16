<?php
/**
 * Database configuration
 */

return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => base_path() . '/database.sqlite',
            'prefix' => '',
        ],
    ],
];

<?php

declare(strict_types=1);

// Create a configuration class
final class Config {
    public const TS3_LIBRARY = '../vendor/planetteamspeak/ts3-php-framework/src/TeamSpeak3.php';

    public const SERVER = [
        'username' => 'serveradmin',
        'password' => 'secure_password',
        'ip' => '127.0.0.1',
        'query_port' => '10011',
        'port' => '9987'
    ];

    public const BOTS = [
        'count' => 0
    ];

    public const ADMIN = [
        'enabled' => false,
        'group_id' => 0
    ];

    public const LOCALE = [
        'timezone' => 'Europe/Berlin',
        'locale' => 'de_DE.utf8',
        'date_type' => 1
    ];

    public const DISPLAY = [
        'background' => [
            'path' => 'images/background.png',
            'blur' => [
                'enabled' => false,
                'intensity' => 10
            ]
        ],
        'font' => [
            'path' => 'fonts/ReadexPro.ttf',
            'sizes' => [
                'time' => 130,
                'server' => 100,
                'normal' => 50,
                'small' => 40,
                'custom' => 2
            ]
        ],
        'colors' => [
            'primary' => [255, 255, 255, 0],
            'secondary' => [105, 108, 124, 0],
            'box' => [0, 0, 0, 70]
        ],
        'text' => [
            'ascii_only' => true,
            'distance' => 10,
            'line_thickness' => 0.5,
            'custom' => ''
        ]
    ];

    public const LOGO = [
        'path' => '',
        'size' => 70
    ];

    public const FEATURES = [
        'version' => true,
        'hostname' => true
    ];

    public const GROUPS = [
        'enabled' => false,
        'max' => 5,
        'position_left' => 9,
        'excluded' => []
    ];
}

// Initialize locale settings
date_default_timezone_set(Config::LOCALE['timezone']);
setlocale(LC_ALL, Config::LOCALE['locale']);
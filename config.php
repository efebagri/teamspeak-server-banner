<?php

declare(strict_types=1);

return [
    // TS3 Framework Configuration
    'ts3' => [
        'library_path' => '../vendor/planetteamspeak/ts3-php-framework/libraries/TeamSpeak3/TeamSpeak3.php',
        'connection' => [
            'username' => 'serveradmin',
            'password' => 'PASSWORD',
            'ip' => '127.0.0.1',
            'query_port' => 10011,
            'server_port' => 9987,
            'bots_count' => 0
        ]
    ],

    // Admin Settings
    'admin' => [
        'enabled' => false,
        'group_id' => 0
    ],

    // Locale Settings
    'locale' => [
        'timezone' => 'Europe/Berlin',
        'locale' => 'de_DE.utf8',
        'date_format' => 'textual' // 'numeric' or 'textual'
    ],

    // Display Settings
    'display' => [
        'background' => [
            'path' => 'images/background.png',
            'blur' => [
                'enabled' => false,
                'intensity' => 10
            ]
        ],
        'text' => [
            'font' => 'fonts/ReadexPro.ttf',
            'ascii_only' => true,
            'sizes' => [
                'time' => 130,
                'server' => 100,
                'normal' => 50,
                'small' => 40,
                'custom' => 2
            ],
            'colors' => [
                'primary' => [255, 255, 255, 0],
                'secondary' => [105, 108, 124, 0]
            ],
            'spacing' => 10,
            'line_thickness' => 0.5,
            'custom_text' => ''
        ],
        'box' => [
            'color' => [0, 0, 0, 70]
        ]
    ],

    // Server Display Options
    'server' => [
        'logo' => [
            'path' => '',
            'size' => 70
        ],
        'show_version' => true,
        'show_hostname' => true
    ],

    // Group Settings
    'groups' => [
        'enabled' => false,
        'max_display' => 5,
        'position_left' => 9,
        'excluded_ids' => []
    ],

    // Translations
    'translations' => [
        'bandwidth' => 'Bandwith (last min)',
        'connection_info' => 'Connection information',
        'upload' => 'Upload (Bytes/s)',
        'download' => 'Download (Bytes/s)',
        'nickname' => 'Nickname',
        'connected_since' => 'Connected since',
        'total_connections' => 'Total connections',
        'groups' => 'Groups',
        'clients' => 'Clients',
        'channels' => 'Channels',
        'workload' => 'Workload',
        'uptime' => 'Uptime',
        'last_joined' => 'Last joined',
        'no_client_info' => 'No client info',
        'vpn_message' => "Check if you are connected \nto a VPN or proxy server and \nreconnect to"
    ]
];
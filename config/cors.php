<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:5173',
        'http://localhost:8080',
        'http://localhost:8081',
        'http://localhost:19006',
        'http://127.0.0.1',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:8080',
        'http://127.0.0.1:8081',
        'http://127.0.0.1:19006',
        'http://fuganda.test',
        'https://fuganda.test',
        'http://fuganda.test:5173',
        'https://fuganda.test:5173',
        'https://mycanopy.verbeek.ug',
        'http://mycanopy.verbeek.ug',
    ],

    'allowed_origins_patterns' => [
        'http://*.localhost*',
        'https://*.localhost*',
        'http://*.verbeek.ug',
        'https://*.verbeek.ug',
        'http://*.ngrok.io',
        'https://*.ngrok.io',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => false,
];

<?php

return [
    'socket_port' => env('SOCKET_PORT', '80'),
    'socket_url' => env('SOCKET_URL', 'http://localhost:8000/graphql'),
    // Você pode até mapear as configurações do DB aqui se quiser centralizar para o Slim:
    'db_host' => env('DB_HOST', '127.0.0.1'),
    'db_user' => env('DB_USERNAME', 'root'),
    'db_pass' => env('DB_PASSWORD', ''),
    'db_name' => env('DB_DATABASE', 'laravel'),
];
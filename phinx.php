<?php

require_once __DIR__ . '/db/Database.php';

$db = new Database(__DIR__ . '/conf/.mysql.localhost');

return [
    'paths' => [
        'migrations' => 'db/migrations',
        'seeds' => 'db/seeds',
    ],
    'environments' => [
        'default_database' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host'    => $db->getHost(),
            'name'    => $db->getDbName(),
            'user'    => $db->getUser(),
            'pass'    => $db->getPassword(),
            'port'    => $db->getPort(),
            'charset' => $db->getCharset(),
        ],
    ],
];

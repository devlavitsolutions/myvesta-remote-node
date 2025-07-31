<?php

// Load Phinx library
require 'phinx/src/Phinx/Console/PhinxApplication.php';
require 'phinx/src/Phinx/Console/PhinxCommand.php';
require 'phinx/src/Phinx/Db/Adapter/MysqlAdapter.php';
require 'phinx/src/Phinx/Db/Adapter/AdapterInterface.php';
require 'phinx/src/Phinx/Db/Table.php';
require 'phinx/src/Phinx/Db/Connection.php';
require 'phinx/src/Phinx/Migration/Manager.php';
require 'phinx/src/Phinx/Migration/AbstractMigration.php';
require 'phinx/src/Phinx/Exception/PhinxException.php';

// Database configuration
$config = [
    'paths' => [
        'migrations' => 'db/migrations', // The directory where migrations will be stored
        'seeds' => 'db/seeds' // The directory where seeds will be stored
    ],
    'environments' => [
        'default_database' => 'development',
        'development' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'hchq',
            'user' => 'root',
            'pass' => '',
            'port' => '3306',
            'charset' => 'utf8',
        ]
    ]
];

// Initialize Phinx
use Phinx\Console\PhinxApplication;

$application = new PhinxApplication();
$application->setConfiguration($config);
$application->run();

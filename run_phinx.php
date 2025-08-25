<?php

require_once 'phinx/src/Phinx/Console/PhinxApplication.php';
require_once 'phinx/src/Phinx/Console/PhinxCommand.php';
require_once 'phinx/src/Phinx/Db/Adapter/MysqlAdapter.php';
require_once 'phinx/src/Phinx/Db/Adapter/AdapterInterface.php';
require_once 'phinx/src/Phinx/Db/Table.php';

// Load Phinx
use Phinx\Console\PhinxApplication;

// Initialize Phinx
$application = new PhinxApplication();

// Set the path to your Phinx configuration
$config = include 'phinx.php';
$application->setConfiguration($config);

// Run the Phinx application
$application->run();
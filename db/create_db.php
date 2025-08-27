<?php

require_once __DIR__ . '/Database.php';

$db = new Database(__DIR__ . '/../conf/.mysql.localhost');

// Ensure database exists
$db->createDatabaseIfNotExists();

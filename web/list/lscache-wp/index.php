<?php
error_reporting(0);
$TAB = 'LITESPEED-CACHE';

// Main include
include($_SERVER['DOCUMENT_ROOT'].'/inc/main.php');
require_once $_SERVER['DOCUMENT_ROOT'] . '/../db/Database.php';

// Initialize Database
$configFile = $_SERVER['DOCUMENT_ROOT'] . '/../conf/.mysql.localhost';
$db = new Database($configFile);
$db->connect();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $server_cache = isset($_POST['server_cache']) ? $_POST['server_cache'] : '';
        $vhost_cache  = isset($_POST['vhost_cache']) ? $_POST['vhost_cache'] : '';

        // Save values to database
        $db->setConfig('server_cache', $server_cache);
        $db->setConfig('vhost_cache', $vhost_cache);

        // Flash success message and redirect
        $_SESSION['flash_success'] = 'Cache locations updated successfully.';
        header('Location: '.$_SERVER['REQUEST_URI']);
        exit;

    } catch (Exception $e) {
        // Flash error message
        $_SESSION['flash_error'] = 'Error updating cache locations: ' . $e->getMessage();
        header('Location: '.$_SERVER['REQUEST_URI']);
        exit;
    }
}

// Fetch saved config for form prefill
$server_cache = $db->getConfig('server_cache');
$vhost_cache  = $db->getConfig('vhost_cache');

// Pass data to frontend
$data = [
    'server_cache' => $server_cache,
    'vhost_cache'  => $vhost_cache
];

// Set back URI
$_SESSION['back'] = $_SERVER['REQUEST_URI'];

render_page($user, $TAB, 'list_litespeed_cache');

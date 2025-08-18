<?php
error_reporting(NULL);
$TAB = 'LITESPEED';

// Main include
include($_SERVER['DOCUMENT_ROOT'].'/inc/main.php');
require_once $_SERVER['DOCUMENT_ROOT'] . '/../db/Database.php';

$output = [];
$return_var = 0;

// Check Apache version
$apache_version = 0;
exec('/usr/sbin/apache2 -v', $output, $return_var);
if (!empty($output)) {
    foreach ($output as $line) {
        if (preg_match('/Apache\/([0-9]+\.[0-9]+\.[0-9]+)/', $line, $matches)) {
            $apache_version = $matches[0];
            break;
        }
    }
}

// Check if Apache is running
exec('systemctl status apache2', $output, $return_var);
$is_apache_running = $return_var === 0;

// Check if LiteSpeed is running
exec('systemctl status lsws', $output, $return_var);
$is_liteSpeed_running = $return_var === 0;

// Check LiteSpeed version
$output = [];
exec('/usr/local/lsws/bin/lshttpd -v', $output);
$liteSpeed_version = 0;
$is_liteSpeed_installed = false;
if (!empty($output)) {
    $is_liteSpeed_installed = true;
    // Loop through the output array to find the version
    foreach ($output as $line) {
        if (preg_match('/.*LiteSpeed.*[0-9]+.*/', $line, $matches)) {
            $liteSpeed_version = $matches[0];
            break;
        }
    }
}

$bash_log = '';

if (isset($_SESSION['bash_log'])) {
    $bash_log = htmlspecialchars($_SESSION['bash_log']);
    unset($_SESSION['bash_log']);
}

// try {
//     $configFile = $_SERVER['DOCUMENT_ROOT'] . '/../conf/.mysql.localhost';
//     $db = new Database($configFile);
//     $db->connect();
//     $rows = $db->select(
//         'lsc_versions',
//         ['version', 'created_at'],
//         1, // LIMIT 1
//         '', // No WHERE condition
//         'ORDER BY created_at DESC'
//     );

//     $latest_version = $rows ? $rows[0]['version'] : null;
// } catch (Exception $ex) {
//     $latest_version = '-';
// }
$latest_version = '7.3.0.1';

render_page($user, $TAB, 'list_litespeed');

// Back uri
$_SESSION['back'] = $_SERVER['REQUEST_URI'];

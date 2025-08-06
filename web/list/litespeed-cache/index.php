<?php
// error_reporting(NULL);
$TAB = 'LSCACHE-WP';


ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        phpinfo();
// Main include
include_once($_SERVER['DOCUMENT_ROOT'].'/inc/main.php');
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/db/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan'])) {
    $json = shell_exec(VESTA_CMD . 'v-scan-websites');
    $data = json_decode($json, true);

    if (!is_array($data)) {
        throw new RuntimeException("Invalid scan output or script error.");
    }

    $db = new Database(dirname($_SERVER['DOCUMENT_ROOT']) . '/conf/.mysql.localhost');
    $db->connect();
    $db->syncDomainsFromScan(data);

    foreach ($data as $site) {
        echo $site['domain'] . ' has WordPress: ' . ($site['wordpress'] ? 'Yes' : 'No') . "<br>";
    }
    exit;
}

render_page($user, $TAB, 'list_lscache_wp');

// Back uri
$_SESSION['back'] = $_SERVER['REQUEST_URI'];

<?php
$TAB = 'LSCACHE-WP';
error_reporting(0);

include_once($_SERVER['DOCUMENT_ROOT'].'/inc/main.php');
require_once __DIR__ . '/../../../db/Database.php';

$db = new Database(__DIR__ . '/../../../conf/.mysql.localhost');
$db->connect();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newVersion = isset($_POST['new_active_version']) ? trim($_POST['new_active_version']) : '';
    if (!empty($newVersion)) {
        try {
            // Store active version in config table
            $db->setConfig('active_lsc_version', $newVersion);
            $_SESSION['flash_success'] = 'Active LSCache version switched to ' . $newVersion;
            header('Location: '.$_SERVER['REQUEST_URI']); // PRG pattern
            exit;
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Error switching version: ' . $e->getMessage();
            header('Location: '.$_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

// Fetch all versions
$rows = $db->select('lsc_versions', ['version']);
$versions = array_column($rows, 'version');

// Sort semantically descending
usort($versions, function($a, $b) { return version_compare($b, $a); });

// Latest version
$latest_version = reset($versions);

// Include missing major versions
$latest_major = (int) explode('.', $latest_version)[0];
$existing_majors = [];
foreach ($versions as $v) {
    $existing_majors[(int) explode('.', $v)[0]] = true;
}
$all_versions_with_missing_majors = $versions;
for ($i = 1; $i <= $latest_major; $i++) {
    if (!isset($existing_majors[$i])) {
        $all_versions_with_missing_majors[] = $i . '.x';
    }
}
usort($all_versions_with_missing_majors, function($a, $b) {
    return version_compare($b, $a);
});

// Fetch current active version from config
$active_version = $db->getConfig('active_lsc_version', $latest_version);

$_SESSION['back'] = $_SERVER['REQUEST_URI'];

render_page($user, $TAB, 'list_lscache_versions');

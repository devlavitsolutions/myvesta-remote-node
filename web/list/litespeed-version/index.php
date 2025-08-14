<?php
$TAB = 'LSCACHE-WP';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include_once($_SERVER['DOCUMENT_ROOT'].'/inc/main.php');
require_once __DIR__ . '/../../../db/Database.php';

$db = new Database(__DIR__ . '/../../../conf/.mysql.localhost');
$db->connect();

// Fetch all versions
$rows = $db->select('lsc_versions', ['version']);
$versions = array_column($rows, 'version');

// Sort semantically in descending order
usort($versions, function($a, $b) {
    return version_compare($b, $a); // reversed order
});

// Latest version will now be first element
$latest_version = reset($versions);
$latest_major = (int) explode('.', $latest_version)[0];

// Find missing majors
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

// Optionally, sort the final array descending
usort($all_versions_with_missing_majors, function($a, $b) {
    return version_compare($b, $a);
});

render_page($user, $TAB, 'list_lscache_versions');
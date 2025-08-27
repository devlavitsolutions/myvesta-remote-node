<?php
$TAB = 'LSCACHE-WP';
error_reporting(0);

include_once($_SERVER['DOCUMENT_ROOT'].'/inc/main.php');
require_once __DIR__ . '/../../../db/Database.php';

$db = new Database(__DIR__ . '/../../../conf/.mysql.localhost');
$db->connect();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- Case 1: Switch Active Version ---
        if (isset($_POST['switchVersion']) && !empty($_POST['new_active_version'])) {
            $newVersion = trim($_POST['new_active_version']);

            // Update DB config
            $db->setConfig('active_lsc_version', $newVersion);

            $_SESSION['flash_success'] = 'Active LSCache version switched to ' . htmlspecialchars($newVersion);

            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        // --- Case 2: Force Upgrade Across Existing Installations ---
        if (isset($_POST['upgrade_action']) && $_POST['upgrade_action'] === 'upgrade') {
            $upgradeTo    = isset($_POST['upgrade_to']) ? trim($_POST['upgrade_to']) : '';
            $fromVersions = isset($_POST['from_versions']) && is_array($_POST['from_versions']) ? $_POST['from_versions'] : [];

            if (empty($upgradeTo) || empty($fromVersions)) {
                $_SESSION['flash_error'] = 'Please select both "From Version(s)" and an "Upgrade To" version.';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }

            $script = VESTA_CMD . "v-upgrade-lsc-version";
            $escapedUpgradeTo = escapeshellarg($upgradeTo);
            $escapedFrom      = array_map('escapeshellarg', $fromVersions);
            $fromString       = implode(' ', $escapedFrom);

            $cmd = "$script --upgrade-to $escapedUpgradeTo --from-versions $fromString 2>&1";

            exec($cmd, $output, $retval);

            if ($retval === 0) {
                $_SESSION['flash_success'] = "Upgrade completed successfully to version $upgradeTo.<br><pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
            } else {
                $_SESSION['flash_error'] = "Upgrade script failed (exit $retval):<br><pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
            }

            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['flash_error'] = 'Error: ' . $e->getMessage();
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// --- Fetch all versions from DB ---
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

#!/usr/bin/php
<?php
/**
 * MyVesta Daily Job - Update LSCache Versions from GitHub
 * Uses credentials from conf/.mysql.localhost
 * Overwrites lsc_versions with only the latest major's versions.
 */

$configFile = __DIR__ . '/conf/.mysql.localhost';

// ===== 1. Parse MySQL credentials =====
if (!file_exists($configFile)) {
    fwrite(STDERR, "Config file not found: $configFile\n");
    exit(1);
}

$conf = parse_ini_file($configFile, false, INI_SCANNER_RAW);
if (!$conf) {
    fwrite(STDERR, "Failed to parse config file.\n");
    exit(1);
}

$dbHost = trim($conf['host'], "'");
$dbUser = trim($conf['user'], "'");
$dbPass = trim($conf['password'], "'");
$dbName = trim($conf['hchq_dbname'], "'");
$dbCharset = isset($conf['hchq_charset']) ? trim($conf['hchq_charset'], "'") : 'utf8';
$dbPort = isset($conf['hchq_port']) ? intval(trim($conf['hchq_port'], "'")) : 3306;

// ===== 2. Fetch tags from GitHub API =====
$repo = 'litespeedtech/lscache_wp';
$url = "https://api.github.com/repos/$repo/tags?per_page=100";

$opts = [
    'http' => [
        'method' => 'GET',
        'header' => [
            "User-Agent: MyVestaScript/1.0\r\n",
            "Accept: application/vnd.github.v3+json\r\n"
        ]
    ]
];
$context = stream_context_create($opts);
$json = file_get_contents($url, false, $context);
if ($json === false) {
    fwrite(STDERR, "Failed to fetch GitHub tags.\n");
    exit(1);
}

$tags = json_decode($json, true);
if (!is_array($tags)) {
    fwrite(STDERR, "Invalid GitHub API response.\n");
    exit(1);
}

$versions = [];
foreach ($tags as $tag) {
    if (isset($tag['name']) && preg_match('/^v?(\d+\.\d+(\.\d+)?)/', $tag['name'], $m)) {
        $versions[] = $m[1];
    }
}
if (!$versions) {
    fwrite(STDERR, "No version tags found.\n");
    exit(1);
}

// ===== 3. Find latest major version =====
usort($versions, 'version_compare');
$latest = end($versions);
$latestMajor = explode('.', $latest)[0];

// Keep only versions from latest major
$filtered = array_filter($versions, fn($v) => explode('.', $v)[0] == $latestMajor);
usort($filtered, 'version_compare');
$filtered = array_reverse($filtered);

// ===== 4. Update database =====
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($mysqli->connect_error) {
    fwrite(STDERR, "DB connection failed: " . $mysqli->connect_error . "\n");
    exit(1);
}
$mysqli->set_charset($dbCharset);

// Clear table
$mysqli->query("TRUNCATE TABLE `lsc_versions`");

// Insert versions
$stmt = $mysqli->prepare("INSERT INTO `lsc_versions` (`version`) VALUES (?)");
foreach ($filtered as $ver) {
    $stmt->bind_param('s', $ver);
    $stmt->execute();
}
$stmt->close();
$mysqli->close();

echo "Inserted " . count($filtered) . " versions for major $latestMajor.\n";

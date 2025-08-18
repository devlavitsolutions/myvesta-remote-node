<?php
$TAB = 'LSCACHE-WP';

include_once($_SERVER['DOCUMENT_ROOT'].'/inc/main.php');
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/db/Database.php';

$db = new Database(dirname($_SERVER['DOCUMENT_ROOT']) . '/conf/.mysql.localhost');
$db->connect();

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32, true));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        // http_response_code(403);
        // die('Invalid CSRF token');
    }

    if (isset($_POST['action']) && $_POST['action'] === 'scan') {
        $json = shell_exec(VESTA_CMD . 'v-scan-websites');
        $data = json_decode($json, true);

        if (!is_array($data)) {
            // You might want to log this error instead of throwing directly
            throw new RuntimeException("Invalid scan output or script error.");
        }

        $db->syncDomainsFromScan($data);

        // Optionally add flash message for success
        $_SESSION['flash_success'] = "Scan completed and synced.";
        
        // Redirect to avoid form resubmission
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $selected = array_map('intval', $_POST['selected']); // sanitize IDs

        if (empty($selected) && !in_array($action, ['unflag_all', 'disable_all', 'enable_all'])) {
            $_SESSION['flash_error'] = "No domains selected.";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        // Prepare comma-separated list for SQL
        $ids = implode(',', $selected);

        switch ($action) {
            case 'enable':
                // Enable cache for selected domains
                $idsArray = array_map('intval', explode(',', $ids));
                $idsList = implode(',', $idsArray);
                $db->update('domains', ['isCacheEnabled' => 1], "id IN ($idsList)");
                $_SESSION['flash_success'] = "Cache enabled for selected domains.";
                break;

            case 'disable':
                $idsArray = array_map('intval', explode(',', $ids));
                $idsList = implode(',', $idsArray);
                $db->update('domains', ['isCacheEnabled' => 0], "id IN ($idsList)");
                $_SESSION['flash_success'] = "Cache disabled for selected domains.";
                break;

            case 'flag':
                $idsArray = array_map('intval', explode(',', $ids));
                $idsList = implode(',', $idsArray);
                $db->update('domains', ['isFlagged' => 1], "id IN ($idsList)");
                $_SESSION['flash_success'] = "Selected domains flagged.";
                break;

            case 'unflag':
                $idsArray = array_map('intval', explode(',', $ids));
                $idsList = implode(',', $idsArray);
                $db->update('domains', ['isFlagged' => 0], "id IN ($idsList)");
                $_SESSION['flash_success'] = "Selected domains unflagged.";
                break;

            case 'unflag_all':
                $db->update('domains', ['isFlagged' => 0]);
                $_SESSION['flash_success'] = "All domains unflagged.";
                break;

            case 'enable_all':
                $db->update('domains', ['isCacheEnabled' => 1], 'isFlagged = 0');
                $_SESSION['flash_success'] = "All domains enabled.";
                break;

            case 'disable_all':
                $db->update('domains', ['isCacheEnabled' => 0], 'isFlagged = 0');
                $_SESSION['flash_success'] = "All domains disabled.";
                break;

            default:
                $_SESSION['flash_error'] = "Unknown action.";
                break;
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Fetch domains to display
$v_domains = $db->select('domains');

// Store current URL to session for back navigation or other use
$_SESSION['back'] = $_SERVER['REQUEST_URI'];

// Pass variables to your rendering function/template
render_page($user, $TAB, 'list_lscache_wp', [
    'v_domains' => $v_domains,
    'csrf_token' => $_SESSION['csrf_token'],
    'flash_success' => isset($_SESSION['flash_success']) ? $_SESSION['flash_success'] : null,
]);

// Clear flash message after displaying once
unset($_SESSION['flash_success']);
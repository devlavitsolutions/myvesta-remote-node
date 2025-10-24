<?php
error_reporting(NULL);

// Main include
include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");

// Check token
if (isset($_GET['token'])) $_POST['token'] = $_GET['token'];
if (!isset($_SESSION['token']) || !isset($_POST['token']) || ($_SESSION['token'] != $_POST['token'])) die('Wrong token');

if (isset($required_dataset_param)) {
    if (!isset($_POST['dataset'])) die('Dataset is required');
    if (!isset($_POST['dataset'][$required_dataset_param])) die($required_dataset_param.' is required');
    if ($required_dataset_param == 'domain') $domain = $_POST['dataset']['domain'];
}

if (isset($required_post_param)) {
    if (!isset($_POST[$required_post_param])) die('POST parameter '.$required_post_param.' is required');
    if ($required_post_param == 'domain') $domain = $_POST[$required_post_param];
}
if (isset($required_get_param)) {
    if (!isset($_GET[$required_get_param])) die('GET parameter '.$required_get_param.' is required');
    if ($required_get_param == 'domain') $domain = $_GET[$required_get_param];
}

$logged_user = $_SESSION['user'];
if (isset($_SESSION['look']) && !empty($_SESSION['look'])) $logged_user = $_SESSION['look'];

if (isset($domain)) {
    $result = exec(VESTA_CMD."v-check-domain-owner ".$logged_user." ".$domain);
        if ($result !== '1') {
            die('Domain is not owned by '.$logged_user);
        }
}

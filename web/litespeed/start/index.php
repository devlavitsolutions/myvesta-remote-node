<?php

error_reporting(NULL);

$root_dir = $_SERVER['DOCUMENT_ROOT'];
$root_dir = str_replace('/web', '', $root_dir);
$script_dir = $root_dir . '/bin/';
$root_dir = escapeshellarg($root_dir);

$action = isset($_GET['action']) ? escapeshellarg($_GET['action']) : '';

$command = 'sudo ' . escapeshellcmd($script_dir . 'v-start-safe-script')
    . ' ' . $action
    . ' ' . $root_dir;

session_start();

exec($command, $output, $return_var);

$_SESSION['bash_log'] = implode(PHP_EOL, $output);

session_write_close();

header("Location: /list/litespeed/");

exit;


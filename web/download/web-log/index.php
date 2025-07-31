<?php
// Init
error_reporting(NULL);
session_start();
include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");

// Check token
if ((!isset($_GET['token'])) || ($_SESSION['token'] != $_GET['token'])) {
    header('Location: /login/');
    exit();
}

if ($_GET['type'] == 'access') $type = 'access';
if ($_GET['type'] == 'error') $type = 'error';

header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=".$_GET['domain'].".".$type."-log.txt");
header("Content-Type: application/octet-stream; "); 
header("Content-Transfer-Encoding: binary");

$v_domain = $_GET['domain'];
if ($_GET['type'] == 'access') $type = 'access';
if ($_GET['type'] == 'error') $type = 'error';


$cmd = implode(" ", array(
    VESTA_CMD . "v-list-web-domain-" . $type . "log",
    escapeshellarg($user),
    escapeshellarg($v_domain),
    "5000",
));

if(is_callable("passthru")){
    passthru($cmd, $return_var);
} else{
    // passthru is disabled, workaround it by writing the output to a file then reading the file. 
    // this is slower than passthru, but avoids running out of RAM...
    $passthru_is_disabled_workaround_handle = tmpfile();
    $passthru_is_disabled_workaround_file = stream_get_meta_data($passthru_is_disabled_workaround_handle)['uri'];
    exec ($cmd . " > " . escapeshellarg($passthru_is_disabled_workaround_file), $output, $return_var);
    readfile($passthru_is_disabled_workaround_file);
    fclose($passthru_is_disabled_workaround_handle); // fclose(tmpfile()) automatically deletes the file, unlink is not required :)
}
if ($return_var != 0) {
    $errstr = "Internal server error: command returned non-zero: {$return_var}: {$cmd}";
    echo $errstr;
    throw new Exception($errstr); // make sure it ends up in an errorlog somewhere
}


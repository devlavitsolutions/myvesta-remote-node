<?php

$myvesta_exit_on_error=true;
$myvesta_quiet_mode=0;
if (isset($_SERVER['MYVESTA_QUIET'])) $myvesta_quiet_mode=intval($_SERVER['MYVESTA_QUIET']);

define('MYVESTA_ERROR_PERMISSION_DENIED', 1);
define('MYVESTA_ERROR_MISSING_ARGUMENTS', 2);
define('MYVESTA_ERROR_FILE_DOES_NOT_EXISTS', 3);
define('MYVESTA_ERROR_STRING_NOT_FOUND', 4);
define('MYVESTA_ERROR_GENERAL', 5);

function myvesta_echo($str) {
    global $myvesta_echo_done, $myvesta_last_echo, $myvesta_quiet_mode;
    if ($myvesta_quiet_mode==1) return;
    $myvesta_echo_done=true;
    $myvesta_last_echo=$str;
    echo $str;
}

function myvesta_exit($code, $echo='') {
    global $SHLVL, $myvesta_echo_done, $myvesta_last_echo, $myvesta_quiet_mode;
    // myvesta_echo ("==================== ".$argv[0].": ".$code." ====================\n");
    if ($myvesta_quiet_mode!=1) {
        if ($echo!='') myvesta_echo($echo);
        if ($SHLVL<3 && $myvesta_echo_done==true) {
            $last_char=substr($myvesta_last_echo, -1, 1);
            if ($last_char!="\n") echo "\n";
        }
    }
    exit($code);
}

$myvesta_current_user=exec('whoami', $myvesta_output, $myvesta_return_var);
if ($myvesta_current_user != 'root') {myvesta_echo ("ERROR: You must be root to execute this script"); myvesta_exit(1);}

function myvesta_throw_error($code, $message) {
    global $myvesta_exit_on_error, $myvesta_quiet_mode;
    if ($message!=='' && $myvesta_quiet_mode!=1) myvesta_echo ("ERROR: ".$message);
    if ($myvesta_exit_on_error) myvesta_exit($code);
    return $code;
}

function myvesta_fix_backslashes($s) {
    $s=str_replace("\\n", "\n", $s);
    $s=str_replace("\\r", "\r", $s);
    $s=str_replace("\\t", "\t", $s);
    return $s;
}

function myvesta_check_args ($requried_arguments, $arguments) {
    global $argv;
    $argument_counter=count($argv);
    $argument_counter--;
    $argv[0]=str_replace('/usr/local/vesta/bin/', '', $argv[0]);
    $command=$argv[0];
    // myvesta_echo ( "-------------------- ".$argv[0]." --------------------\n");
    if ($argument_counter<$requried_arguments) {
        $arguments=str_replace(" ", "' '", $arguments);
        $arguments="'".$arguments."'";
        return myvesta_throw_error(MYVESTA_ERROR_MISSING_ARGUMENTS, "Usage: $command $arguments");
    }
    $argument_arr=explode(" ", $arguments);
    $i=1;
    foreach ($argument_arr as $argument) {
        $GLOBALS[$argument]=myvesta_fix_backslashes($argv[$i]);
        $i++;
    }
}

function myvesta_fix_args() {
    global $argv;
    $i=0;
    foreach ($argv as $argument) {
        if ($i==0) {$i++; continue;}
        $argv[$i]=myvesta_fix_backslashes($argv[$i]);
        $i++;
    }
}

function myvesta_test_func () {
    $args=func_get_args();
    myvesta_echo ("You said: ");
    myvesta_echo (trim(print_r ($args, true)));
}

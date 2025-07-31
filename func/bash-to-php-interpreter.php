<?php

$myvesta_echo_done=false;

if (isset($_SERVER['SHLVL'])) $SHLVL=intval($_SERVER['SHLVL']);
else $SHLVL=3;

if (!isset($argv)) exit(5);

$argv_start=1;
$STDIN_ENABLED=false;
if ($argv[1]=='--stdin') {
    $STDIN_ENABLED=true;
    $argv_start++;
}

$myvesta_stdin='';
if ($STDIN_ENABLED==true) {
    stream_set_blocking(STDIN, false);
    $myvesta_f = fopen( 'php://stdin', 'r' );
    while( $myvesta_line = fgets( $myvesta_f ) ) {
        $myvesta_stdin .= $myvesta_line;
    }
    fclose( $myvesta_f );
}

include ("/usr/local/vesta/func/main.php");
include ("/usr/local/vesta/func/string.php");

$counter=count($argv);
if ($counter<2) myvesta_throw_error(2, 'Function is missing');

$func=$argv[$argv_start];
if (!function_exists($func)) {
    $func="myvesta_".$argv[$argv_start];    
    if (!function_exists($func)) myvesta_throw_error(2, 'Function does not exists');
}

$insert_stdin_at_position=false;
if ($func=="myvesta_grep") $insert_stdin_at_position=1;
if ($func=="myvesta_sed") $insert_stdin_at_position=2;

$params=array();

$added=0;
$stdin_content='';
$myvesta_stdin_from_file='';
$myvesta_stdin_return_not_found=false;
if ($myvesta_stdin!='' && $insert_stdin_at_position===false) {$params[]=$myvesta_stdin; $added++;}

$argv_start++;

for ($i=$argv_start; $i<$counter; $i++) {
    $argv[$i]=myvesta_fix_backslashes($argv[$i]);
    //if ($insert_stdin_at_position!==false && $myvesta_stdin=='') if ($insert_stdin_at_position==$added) {$stdin_content=$argv[$i]; $added++; continue;}
    $params[]=$argv[$i];
    $added++;
}
//print_r($params); exit;

if ($insert_stdin_at_position!=false) {
    if ($myvesta_stdin=='' && isset($params[$insert_stdin_at_position])) {
        $file_or_stdin=$params[$insert_stdin_at_position];
        if (!file_exists($file_or_stdin)) {
            $myvesta_stdin_return_not_found=true;
            $myvesta_stdin='';
        } else {
            $myvesta_stdin=file_get_contents($file_or_stdin);
            $myvesta_stdin_from_file=$file_or_stdin;
        }
        $params[$insert_stdin_at_position]=$myvesta_stdin;
    } else {
        array_splice($params, $insert_stdin_at_position, 0, array($myvesta_stdin));
    }
}
//print_r($params); exit;

$r=call_user_func_array($func, $params);
if (is_bool($r)) {
    if ($r) {
        myvesta_exit (0);
    } else {
        myvesta_exit (MYVESTA_ERROR_GENERAL);
    }
} else {
    myvesta_echo ($r);
    myvesta_exit (0);
}

<?php

// The script is checking if MySQL/MariaDB server process is up.
// If it's not, it assumes that it was killed by Kernel OOM Killer, and reboots the server, in order to bring the back server to a normal state.
// It can detect 'apt upgrade' process and avoid a reboot.

// You can put this script in cronjob every 5 minutes:
// Run 'crontab -e' and add the following:
// */5 * * * * php /home/scanps.php > /dev/null 2>&1

function my_exec($command) {
    $out=array();
    $ret_no=0;
    if (strpos($command, " > ")!==FALSE) $command.=" 2> /dev/null";
    else {
        if (strpos($command, " 2>&1")===FALSE) $command.=" 2>&1";
    }
    $ret = exec($command, $out, $ret_no);
    return implode("\n", $out);
}

function is_there($list, $what) {
    $arr=explode("\n", $list);
    $c=count($arr);
    for ($i=1; $i<$c; $i++) if (strpos($arr[$i], $what)!==false) return true;
    return false;
}

$list=my_exec("ps -Af");
if (is_there($list, "apt")) exit; // the server is in upgrading proccess

$search_for1="mysqld";
$search_for2="mariadbd";
$v1=is_there($list, $search_for1);
$v2=is_there($list, $search_for2);
$sufix="";

if ($v1==false && $v2==false) {
    echo "- reboot\n";
    $buffer="- reboot\n".$list;
    $sufix="_".time();
    $fp = fopen('/var/log/scanps_cron'.$sufix.'.log', 'w');
    fwrite($fp, $buffer);
    fclose($fp);
    $out=array();
    $ret_no=0;
    $uname_arr=posix_uname();
    $hostname=$uname_arr['nodename'];
    $email=my_exec("export VESTA=/usr/local/vesta && /usr/local/vesta/bin/v-list-user 'admin' | grep 'EMAIL' | awk '{print $2}'");
    mail($email, 'VPS reboot - '.$hostname, $buffer, "From: ".$hostname." <admin@".$hostname.">");
    sleep(10);
    $ret = exec("sudo reboot", $out, $ret_no);
    exit;
} else {
    echo "- mysql ok\n";
    $fp = fopen('/var/log/scanps_cron.log', 'w');
    fwrite($fp, "- mysql ok");
    fclose($fp);
    exit;
}

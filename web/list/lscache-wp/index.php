<?php
error_reporting(NULL);
$TAB = 'LITESPEED-CACHE';

// Main include
include($_SERVER['DOCUMENT_ROOT'].'/inc/main.php');

render_page($user, $TAB, 'list_litespeed_cache');

// Back uri
$_SESSION['back'] = $_SERVER['REQUEST_URI'];

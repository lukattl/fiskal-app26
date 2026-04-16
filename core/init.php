<?php
if (!isset($_SESSION)) {
	session_start();
}

ini_set('display_errors', 1);

// Logging Die&Dump
function dd($data = 'ok', $data1 = ''){
	echo '<pre>';
	var_dump($data);
	var_dump($data1);
	echo '</pre>';
	die();
}

$Froot = dirname(dirname(__FILE__));

spl_autoload_register(function ($class){
	$Froot = dirname(dirname(__FILE__));
	require_once("{$Froot}/classes/{$class}.php");
});
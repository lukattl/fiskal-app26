<?php
if (!isset($_SESSION)) {
	session_start();
}

ini_set('display_errors', 1);

function dd($data = 'ok', $data1 = ''){
	echo '<pre>';
	var_dump($data);
	var_dump($data1);
	echo '</pre>';
	die();
}


$F2root = dirname(dirname(__FILE__));
#require_once("{$F2root}/functions/sanitize.php");
#require_once("{$F2root}/functions/debug.php");

spl_autoload_register(function ($class){
	$F2root = dirname(dirname(__FILE__));
	require_once("{$F2root}/classes/{$class}.php");
});
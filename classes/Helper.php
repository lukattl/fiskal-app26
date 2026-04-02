<?php

class Helper
{
	# zabrana instanciranja objekta
	private function __construct(){}
	
	#zabrana kloniranja objekta
	private function __clone(){}
	
	public static function getHeader($title, $user = null, $file='header')
	{
		if($file){
			$path = require_once '../includes/'. $file .'.php';
			return $path;
		}
		return false;
	}
	
	public static function getFooter($page, $file='footer', $user = null)
	{
		if($file){
			$path = require_once '../includes/'. $file .'.php';
			return $path;
		}
		return false;
	}
	
	public static function getModal($file = null, $user = null)
	{
		if($file){
			$path = require_once '../modals/'. $file .'.php';
			return $path;
		}
		return false;
	}

	public static function getConfig($file)
	{
		if($file){
			$path = require '../config/'. $file .'.php';
			return $path;
		}
		return false;
	}

	public static function now()
	{
		return (new \DateTime())->format('Y-m-d H:i:s');
	}



	/**
	// @ output = array()
	// @ return json output for Android
	**/
	/* public static function respond($output = array()) {
    	return json_encode($output, JSON_PRETTY_PRINT);
	} */

	public static function respond($respond) {
		$locations[] = $respond;
		return json_encode($locations, JSON_PRETTY_PRINT);
	}


	
}
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

	public static function toArray($data)
	{
		if (is_array($data)) {
			$result = [];
			foreach ($data as $key => $value) {
				$result[$key] = self::toArray($value);
			}
			return $result;
		}

		if (is_object($data)) {
			return self::toArray(get_object_vars($data));
		}

		return $data;
	}

	public static function storeAuthenticatedUser($user)
	{
		$userData = self::toArray($user);

		if (isset($userData['password'])) {
			unset($userData['password']);
		}

		$_SESSION['auth_user'] = $userData;
		$_SESSION['user_id'] = $userData['id'] ?? null;
		$_SESSION['full_name'] = $userData['full_name'] ?? '';
		$_SESSION['email'] = $userData['email'] ?? '';
		$_SESSION['company_id'] = $userData['company_id'] ?? null;

		$_SESSION['auth_company'] = [];

		if (!empty($userData['company_id'])) {
			$db = DB::getInstance();
			$companyQuery = $db->query("SELECT * FROM companys WHERE id = ?", [$userData['company_id']]);

			if (!$companyQuery->getError() && $companyQuery->getResults()) {
				$_SESSION['auth_company'] = self::toArray($companyQuery->getFirst());
			}
		}
	}

	public static function requireAuth($redirect = '../index.php')
	{
		if (!isset($_SESSION['user_id'])) {
			header("Location: {$redirect}");
			exit;
		}

		return self::currentUser();
	}

	public static function currentUser()
	{
		return $_SESSION['auth_user'] ?? [];
	}

	public static function currentCompany()
	{
		return $_SESSION['auth_company'] ?? [];
	}

	public static function refreshAuthenticatedUser()
	{
		$userId = $_SESSION['user_id'] ?? null;

		if (!$userId) {
			return false;
		}

		$db = DB::getInstance();
		$userQuery = $db->query("SELECT * FROM users WHERE id = ?", [$userId]);

		if ($userQuery->getError() || !$userQuery->getResults()) {
			return false;
		}

		self::storeAuthenticatedUser($userQuery->getFirst());
		return true;
	}

	public static function currentBusinessUnitId()
	{
		$user = self::currentUser();
		$company = self::currentCompany();

		if (!empty($user['bunit_id'])) {
			return $user['bunit_id'];
		}

		if (!empty($company['bunit_id'])) {
			return $company['bunit_id'];
		}

		if (!empty($company['id'])) {
			$db = DB::getInstance();
			$bunitQuery = $db->query('SELECT id FROM business_units WHERE company_id = ?', [$company['id']], 1);

			if (!$bunitQuery->getError() && $bunitQuery->getResults()) {
				$bunit = self::toArray($bunitQuery->getFirst());
				return $bunit['id'] ?? null;
			}
		}

		return null;
	}


	
}

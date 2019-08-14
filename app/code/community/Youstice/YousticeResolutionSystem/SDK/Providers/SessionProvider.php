<?php

class Youstice_Providers_SessionProvider implements Youstice_Providers_SessionProviderInterface {

	public function start()
	{
		if (!Youstice_Helpers_HelperFunctions::isSessionStarted())
			session_start();
	}

	public function get($var)
	{
		if (!isset($_SESSION['YRS']))
			return false;

		if (!isset($_SESSION['YRS'][$var]))
			return false;

		return $_SESSION['YRS'][$var];
	}

	public function set($var, $value)
	{
		if (!isset($_SESSION['YRS']))
			$_SESSION['YRS'] = array();

		$_SESSION['YRS'][$var] = $value;
	}

	public function destroy()
	{
		if (isset($_SESSION['YRS']))
			$_SESSION['YRS'] = array();
	}

}

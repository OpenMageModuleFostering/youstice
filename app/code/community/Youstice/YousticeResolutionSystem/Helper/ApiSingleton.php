<?php

/**
 * Description of ApiSingleton
 *
 */
class Youstice_YousticeResolutionSystem_Helper_ApiSingleton {

	protected $api;

	function __construct() {
		require_once(__DIR__ . '/../SDK/Api.php');

		$this->api = Youstice_Api::create();

		$this->api->setDbCredentials($this->getDbCredentials())
				->setLanguage($this->getLanguage())
				->setShopSoftwareType('magento', Mage::getVersion())
				->setThisShopSells(Mage::getStoreConfig('youstice/shop_sells'))
				->setApiKey(Mage::getStoreConfig('youstice/api_key'), Mage::getStoreConfig('youstice/use_sandbox'))
				->setUserId($this->getCustomerId());

		$this->api->run();
	}

	public function get() {
		return $this->api;
	}

	protected function getDbCredentials() {
		$config = Mage::getConfig()->getResourceConnectionConfig('default_setup');
		$driver = $config->type->asArray();
		
		$array = array(
			'driver' => str_replace('pdo_', '', $driver),
			'host' => $config->host->asArray(),
			'user' => $config->username->asArray(),
			'pass' => $config->password->asArray(),
			'name' => $config->dbname->asArray(),
			'prefix' => (string) Mage::getConfig()->getTablePrefix()
		);
		
		return $array;
	}

	public function getCustomerId() {
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customer = Mage::getSingleton('customer/session');
			
			return $customer->getId();
		}

		return null;
	}
	
	protected function getLanguage() {
		if(Mage::getStoreConfig('youstice/default_language'))
			return Mage::getStoreConfig('youstice/default_language');
		
		$x = Mage::app()->getStore()->getCode();
		
		if($x == 'admin' || $x == 'default')
			return 'en';
		
		return $x;
	}

}

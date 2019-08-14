<?php

$this->setConfigData('youstice/api_key', '');
$this->setConfigData('youstice/use_sandbox', 1);
$this->setConfigData('youstice/shop_sells', 'product');
$this->setConfigData('youstice/default_language', 'en');
$this->setConfigData('youstice/db_installed', 1);

Mage::getConfig()->reinit();
Mage::app()->reinitStores();

$api = Mage::getSingleton('Youstice_YousticeResolutionSystem_Helper_ApiSingleton');

try {
	$api->get()->install();
}
catch (Youstice_ApiException $e) {
	//do nothing here, raise error in admin
	$this->setConfigData('youstice/db_installed', 0);
}
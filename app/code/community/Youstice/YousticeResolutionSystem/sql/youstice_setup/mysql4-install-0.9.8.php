<?php

$this->setConfigData('youstice/api_key', '');
$this->setConfigData('youstice/use_sandbox', 1);
$this->setConfigData('youstice/shop_sells', 'product');
$this->setConfigData('youstice/default_language', 'en');

Mage::getConfig()->reinit();
Mage::app()->reinitStores();
 
$api = Mage::getSingleton('Youstice_YousticeResolutionSystem_Helper_ApiSingleton');

$api->get()->install();
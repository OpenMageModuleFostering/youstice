<?php

/**
 * Description of Youstice_YousticeResolutionSystem_AdminController
 *
 */
class Youstice_YousticeResolutionSystem_AdminController extends Mage_Adminhtml_Controller_Action {

	public function indexAction() {
		$this->loadLayout();

		$this->_setActiveMenu('youstice_yousticeresolutionsystem');

		$this->renderLayout();
	}
	
	public function saveAction() {
		$params = $this->getRequest()->getPost();		
		$api = Mage::getSingleton('Youstice_YousticeResolutionSystem_Helper_ApiSingleton')->get();
		
		$validApiKey = $this->checkApiKey($params['api_key'], $params['use_sandbox']);
		
		if(!$validApiKey) {
			Mage::getSingleton('core/session')->addError($api->t('Invalid API KEY'));
			$this->_redirect('youstice/admin');
		}
		
		Mage::getModel('core/config')->saveConfig('youstice/api_key', $params['api_key']);
		Mage::getModel('core/config')->saveConfig('youstice/use_sandbox', $params['use_sandbox']);
		//Mage::getModel('core/config')->saveConfig('youstice/shop_sells', $params['shop_sells']);
		Mage::getModel('core/config')->saveConfig('youstice/default_language', $params['default_language']);
		
		Mage::getConfig()->reinit();
		Mage::app()->reinitStores();

		
		Mage::getSingleton('core/session')->addSuccess($api->t('Settings were saved successfully.'));
		$this->_redirect('youstice/admin');
	}
	
	//ajax
	public function checkApiKeyAction() {
		$params = $this->getRequest()->getPost();
		
		$result = $this->checkApiKey($params['api_key'], $params['use_sandbox']);

		exit(json_encode(array('result' => $result)));
	}
	
	private function checkApiKey($apiKey, $useSandbox) {
		if (!trim($apiKey))
			return false;
		
		$api = Mage::getSingleton('Youstice_YousticeResolutionSystem_Helper_ApiSingleton')->get();

		$api->setApiKey($apiKey, $useSandbox);
		$api->runWithoutUpdates();

		$result = false;

		try {
			$result = $api->checkApiKey();
		}
		catch(Exception $e) {
			$result = false;
		}

		return $result;
	}

}

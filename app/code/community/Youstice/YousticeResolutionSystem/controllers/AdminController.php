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
	
	//ajax
	public function checkApiKeyAction() {
		$params = $this->getRequest()->getPost();
		
		$result = $this->checkApiKey($params['api_key'], $params['use_sandbox']);
		
		if($result == true)
			$this->saveForm();

		exit(json_encode(array('result' => $result)));
	}
	
	protected function saveForm() {
		$params = $this->getRequest()->getPost();		
		$api = Mage::getSingleton('Youstice_YousticeResolutionSystem_Helper_ApiSingleton')->get();
		
		Mage::getModel('core/config')->saveConfig('youstice/api_key', $params['api_key']);
		Mage::getModel('core/config')->saveConfig('youstice/use_sandbox', $params['use_sandbox']);
		//Mage::getModel('core/config')->saveConfig('youstice/shop_sells', $params['shop_sells']);
		Mage::getModel('core/config')->saveConfig('youstice/default_language', $params['default_language']);
		
		Mage::getConfig()->reinit();
		Mage::app()->reinitStores();
		
		Mage::getSingleton('core/session')->addSuccess($api->t('Settings were saved successfully.'));
		$this->_redirect('youstice/admin');
	}
	
	protected function checkApiKey($apiKey, $useSandbox) {
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
			$result = 'fail';
		}

		return $result;
	}

}

<?php

/**
 * Description of Youstice_YousticeResolutionSystem_IndexController
 *
 */
class Youstice_YousticeResolutionSystem_IndexController extends Mage_Core_Controller_Front_Action {

	protected $api;
	protected $customer_id;

	public function preDispatch() {
		parent::preDispatch();

		$this->api = Mage::getSingleton('Youstice_YousticeResolutionSystem_Helper_ApiSingleton');

		$this->authenticateUser();
		$this->api->get()->setUserId($this->customer_id);
	}

	public function getReportClaimsPageAction() {
		if ($this->api->getCustomerId() !== null)
			$this->_redirectUrl(Mage::getUrl('sales/order/history'));

		$this->loadLayout();
		
		$content = $this->getLayout()->createBlock('page/html')->setName('reportClaims')->setTemplate('youstice/reportClaims.phtml');		
		$this->getLayout()->getBlock('content')->append($content);

		$ogTags = $this->getLayout()->createBlock('page/html')->setName('reportClaimsOgTags')->setTemplate('youstice/reportClaimsOgTags.phtml');
		$this->getLayout()->getBlock('head')
				->append($ogTags)
				->addJs('youstice/reportClaims.js', 'name=last');	//name allows to add this script before jquery
		
		$this->renderLayout();
	}

	public function getReportClaimsPagePostAction() {
		$order_number = $this->getOrderNumber();

		if (!$this->customer_id) {
			echo json_encode(array('error' => 'Invalid email'));
			exit;
		}

		$order = Mage::getModel('sales/order')->loadByIncrementId($order_number);

		if ($order->getId()) {
			$shop_order = $this->createShopOrder($order);

			$html = $this->api->get()->getOrderDetailHtml($shop_order);
			echo json_encode(array('orderDetail' => $html));
			exit;
		}

		//order number not found in customer's orders
		echo json_encode(array('error' => 'Email or order number not found'));
		exit;
	}

	protected function authenticateUser() {
		if ($this->api->getCustomerId() !== null) {
			$this->customer_id = $this->api->getCustomerId();
			return;
		}

		$email = $this->getRequest()->getParam('email');

		if (!Zend_Validate::is($email, 'EmailAddress'))
			return;

		$customer = Mage::getModel("customer/customer");
		$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
		$customer->loadByEmail($email);

		if ($customer->getId() !== null) {
			$this->customer_id = $customer->getId();
		}
	}

	protected function getOrderNumber() {
		return preg_replace('/[^\w\d]/ui', '', $this->getRequest()->getParam('orderNumber'));
	}

	public function getShowButtonsHtmlAction() {
		echo $this->api->get()->getShowButtonsWidgetHtml();
		$this->api->get()->orderHistoryViewed();
	}

	public function getLogoWidgetAction() {
		echo $this->api->get()->getLogoWidgetHtml(Mage::getUrl('youstice/index/getReportClaimsPage'));
	}

	public function getWebReportButtonAction() {
		echo $this->api->get()->getWebReportButtonHtml(Mage::getUrl('youstice/index/createWebReport/'));
	}

	public function getOrdersButtonsAction() {

		$params = $this->getRequest()->getParams();
		foreach ($params['order_ids'] as $orderId) {
			$order = Mage::getModel('sales/order')->load($orderId);

			if ($this->api->getCustomerId() !== $order['customer_id'])
				continue;

			$shopOrder = $this->createShopOrder($orderId);

			$orderDetailUrl = Mage::getUrl('youstice/index/getOrderDetail', array('_query' => 'order_id=' . $orderId));

			$response[$orderId] = $this->api->get()->getOrderDetailButtonHtml($orderDetailUrl, $shopOrder);
		}

		echo json_encode($response);
	}

	public function getProductsButtonsAction() {
		$params = $this->getRequest()->getParams();
		$orderId = $params['order_id'];

		$order = Mage::getModel('sales/order')->load($orderId);

		if ($this->api->getCustomerId() !== $order['customer_id'])
			exit;

		$shopOrder = $this->createShopOrder($orderId);
		$products = $shopOrder->getProducts();

		if (count($products) === 0)
			exit;

		$response = array();
		foreach ($products as $shopProduct) {
			$productSku = $shopProduct->getId();

			$params = array('order_id' => $orderId, 'product_sku' => $productSku);
			$link = Mage::getUrl('youstice/index/createProductReport', array('_query' => $params));

			$response[$productSku] = $this->api->get()->getProductReportButtonHtml($link, $productSku, $orderId);
		}

		echo json_encode($response);
	}

	public function getOrderDetailAction() {
		$params = $this->getRequest()->getParams();
		$orderId = (int)$params['order_id'];
		
		$order = Mage::getModel('sales/order')->load($orderId);
		
		if ($this->api->getCustomerId() !== $order['customer_id'])
			exit;

		$shopOrder = $this->createShopOrder($order);

		echo $this->api->get()->getOrderDetailHtml($shopOrder);
	}

	public function createWebReportAction() {
		try {
			$redirectUrl = $this->api->get()->createWebReport();
		} catch (\Exception $e) {
			exit('Connection to remote server failed, please <a href="#" onClick="history.go(0)">try again</a> later');
		}

		$this->_redirectUrl($redirectUrl);
	}

	public function createOrderReportAction() {
		//logged out reporting
		if (!Mage::getSingleton('customer/session')->isLoggedIn())
			$order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderNumber());
		else {
			$order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('order_id'));
		
			if ($this->api->getCustomerId() !== $order['customer_id'])
				exit;
		}

		$shopOrder = $this->createShopOrder($order->getId());
		
		if ($order->getCustomerId() !== $this->customer_id)
			exit('Order not found');

		try {
			$link = $this->api->get()->createOrderReport($shopOrder);
		} catch (\Exception $e) {
			exit('Connection to remote server failed, please <a href="#" onClick="history.go(0)">try again</a> later');
		}

		$this->_redirectUrl($link);
	}

	public function createProductReportAction() {
		//logged out reporting
		if (!Mage::getSingleton('customer/session')->isLoggedIn())
			$order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderNumber());
		else {
			$order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('order_id'));
		
			if ($this->api->getCustomerId() !== $order['customer_id'])
				exit;
		}

		$productSku = $this->getRequest()->getParam('product_sku');

		$shopOrder = $this->createShopOrder($order->getId());

		$shopProducts = $shopOrder->getProducts();

		if ($order->getCustomerId() === $this->customer_id) {
			foreach ($shopProducts as $shopProduct) {
				if ($shopProduct->getId() == $productSku) {
					try {
						$link = $this->api->get()->createProductReport($shopProduct);
					} catch (\Exception $e) {
						exit('Connection to remote server failed, please <a href="#" onClick="history.go(0)">try again</a> later');
					}

					$this->_redirectUrl($link);
				}
			}
		}

		echo('Product not found');
	}

	protected function createShopOrder($order) {
		if (!$order instanceof Mage_Sales_Model_Order)
			$order = Mage::getModel('sales/order')->load($order);

		$products = $order->getAllItems();

		$deliveryDate = 0;
		/** @var $shipment Mage_Sales_Model_Order_Shipment */
		foreach ($order->getShipmentsCollection() as $shipment) {
			$deliveryDate = $shipment->getCreatedAt();
		}

		$shopOrder = Youstice_ShopOrder::create()
				->setDescription('not provided')
				->setName('#' . $order['increment_id'])
				->setCurrency($order['order_currency_code'])
				->setPrice((float) $order['grand_total'])
				->setId($order->getId())
				->setDeliveryDate($deliveryDate)
				->setOrderDate($order['created_at'])
				->setOtherInfo(json_encode($order->getData()))
				->setHref($this->createOrderReportHref($order->getId()));

		foreach ($products as $product) {
			$shopProduct = $this->createShopProduct($product, $order->getId());
			$shopProduct->setCurrency($order['order_currency_code']);
			$shopProduct->setDeliveryDate($deliveryDate);
			$shopProduct->setOrderDate($order['created_at']);

			$shopOrder->addProduct($shopProduct);
		}

		return $shopOrder;
	}

	protected function createShopProduct($product, $orderId) {
		$productObj = Mage::getModel('catalog/product')->load($product->getProductId());
		$productSku = $product->getSku();

		$shopProduct = Youstice_ShopProduct::create()
				->setDescription($productObj->description)
				->setName($product->getName())
				->setPrice((float) $product->getPrice())
				->setId($productSku)
				->setOtherInfo(json_encode($product->getData()));

		//add image if exists
		if (strlen($productObj['image']) && $productObj['image'] != 'no_selection') {
			$imagePath = Mage::getBaseDir('media') . '/catalog/product' . $productObj['image'];
			$shopProduct->setImagePath($imagePath);
		}

		$shopProduct->setOrderId($orderId);
		$shopProduct->setHref($this->createProductReportHref($orderId, $productSku));

		return $shopProduct;
	}

	protected function createOrderReportHref($orderId) {
		//logged out reporting
		if (!Mage::getSingleton('customer/session')->isLoggedIn())
			$params = array('email' => $this->getRequest()->getParam('email'), 'orderNumber' => $this->getRequest()->getParam('orderNumber'));
		else
			$params = array('order_id' => $orderId);

		$href = Mage::getUrl('youstice/index/createOrderReport', array('_query' => $params));
		return $href;
	}

	protected function createProductReportHref($orderId, $productId) {
		//logged out reporting
		if (!Mage::getSingleton('customer/session')->isLoggedIn())
			$params = array('email' => $this->getRequest()->getParam('email'), 'orderNumber' => $this->getRequest()->getParam('orderNumber'));
		else
			$params = array('order_id' => $orderId);
		
		$params = array_merge(array('product_sku' => $productId), $params);
		$href = Mage::getUrl('youstice/index/createProductReport', array('_query' => $params));

		return $href;
	}

}

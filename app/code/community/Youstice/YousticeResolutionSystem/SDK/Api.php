<?php

/**
 * Main Youstice class.
 *
 * @author    Youstice
 * @copyright (c) 2015, Youstice
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 */

/**
 * Youstice main API class
 *
 * @author KBS Development
 */
class Youstice_Api {

	/**
	 * Because updateData function is called every request, update only every 10 minutes
	 * @var int
	 */
	protected $update_interval = 600;

	/**
	 * When setOftenUpdates was called, next 5 minutes updates occurs
	 * @var int
	 */
	protected $often_update_interval = 300;

	/**
	 *
	 * @var type Youstice_Translator
	 */
	protected $translator;

	/**
	 *
	 * @var SessionProviderInterface
	 */
	protected $session;

	/**
	 *
	 * @var type Youstice_LocalInterface
	 */
	protected $local;

	/**
	 * ISO 639-1 char code "en|sk|cz|es"
	 * @var string
	 */
	protected $language;

	/**
	 * string from youstice service
	 * @var string
	 */
	protected $api_key;

	/**
	 * product|service
	 * @var string
	 */
	protected $shop_sells;

	/**
	 * unique integer identifier
	 * @var type
	 */
	protected $user_id;

	/**
	 * true for testing environment
	 * @var boolean
	 */
	protected $use_sandbox;

	/**
	 * prestashop|magento|ownSoftware
	 * @var string
	 */
	protected $shop_software_type;

	/**
	 * e.g. 1.9.4.2
	 * @var string
	 */
	protected $shop_software_version;

	/*
	 * Is true when curl, PDO and fileinfo are available
	 */
	protected $is_properly_installed = false;

	const CURL_NOT_INSTALLED = 1;
	const PDO_NOT_INSTALLED = 2;
	const FINFO_NOT_INSTALLED = 3;

	/**
	 *
	 * @param array $db_credentials associative array for PDO connection with must fields: driver, host, name, user, pass
	 * @param string $language ISO 639-1 char code "en|sk|cz|es"
	 * @param string $api_key string from youstice service
	 * @param string $shop_sells "product|service"
	 * @param integer $user_id unique integer for user
	 * @param boolean $use_sandbox true if testing implementation
	 * @param string $shop_software_type prestashop|magento|ownSoftware
	 * @param string $shop_software_version e.g. 1.9.4.2
	 * @return Youstice_Api
	 */
	public static function create(array $db_credentials = array(), $language = 'sk', $api_key = '', $shop_sells = 'product', $user_id = null, $use_sandbox = false, $shop_software_type = 'custom', $shop_software_version = '')
	{
		return new self($db_credentials, $language, $api_key, $shop_sells, $user_id, $use_sandbox, $shop_software_type, $shop_software_version);
	}

	/**
	 *
	 * @param array $db_credentials associative array for PDO connection with must fields: driver, host, name, user, pass
	 * @param string $language ISO 639-1 char code "en|sk|cz|es"
	 * @param string $api_key string from youstice service
	 * @param string $shop_sells "product|service"
	 * @param integer $user_id unique integer for user
	 * @param boolean $use_sandbox true if testing implementation
	 * @param string $shop_software_type prestashop|magento|ownSoftware
	 * @param string $shop_software_version e.g. 1.9.4.2
	 * @return Youstice_Api
	 */
	public function __construct(array $db_credentials = array(), $language = 'sk', $api_key = '', $shop_sells = 'product', $user_id = null, $use_sandbox = false, $shop_software_type = 'custom', $shop_software_version = '')
	{
		$this->registerAutoloader();

		$this->setDbCredentials($db_credentials);
		$this->setLanguage($language);
		$this->setUserId($user_id);
		$this->setApiKey($api_key, $use_sandbox);
		$this->setThisShopSells($shop_sells);
		$this->setShopSoftwareType($shop_software_type, $shop_software_version);

		return $this;
	}

	/**
	 * Start Youstice API
	 * @return Youstice_Api
	 */
	public function run()
	{
		$this->runWithoutUpdates();

		$this->updateData();

		return $this;
	}

	/**
	 * Start Youstice API and do not run updates
	 * @return YousticeApi
	 */
	public function runWithoutUpdates()
	{
		$this->checkShopSells();

		if (!$this->session)
			$this->setSession(new Youstice_Providers_SessionProvider());

		$this->is_properly_installed = $this->checkIsProperlyInstalled();

		$this->remote = new Youstice_Remote($this->api_key, $this->use_sandbox, $this->language, $this->shop_sells, $this->shop_software_type, $this->shop_software_version);

		return $this;
	}

	/**
	 * Helper function for autoloading classes (called in constructor)
	 */
	protected function registerAutoloader()
	{
		spl_autoload_register(function ($class_name) {
			$class_name = str_replace('Youstice_', '', $class_name);
			$class_path = str_replace('_', DIRECTORY_SEPARATOR, $class_name);

			$path = dirname(__FILE__) . DIRECTORY_SEPARATOR . $class_path;

			if (is_readable($path . '.php'))
				require_once $path . '.php';
		}, true, true);  //prepend our autoloader
	}

	/**
	 * Renders form with fields email and orderNumber for reporting claims
	 * @return string html
	 */
	public function getReportClaimsFormHtml()
	{
		if (!trim($this->api_key))
			return "Invalid shop's api key";

		if (!$this->is_properly_installed)
			return 'Youstice plugin is not properly installed';

		$widget = new Youstice_Widgets_ReportClaimsForm($this->language);

		return $widget->toString();
	}

	public function getShowButtonsWidgetHtml()
	{
		if (!trim($this->api_key))
			return '';

		if (!$this->is_properly_installed)
			return '';

		$reports_count = count($this->local->getReportsByUser($this->user_id));

		$widget = new Youstice_Widgets_ShowButtons($this->language, $reports_count > 0);

		return $widget->toString();
	}

	public function getOrdersPageWidgetHtml($webReportHref, $shopName, array $shopOrders)
	{
		if (!trim($this->api_key))
			return 'No orders have been found';

		if (!$this->is_properly_installed)
			return '';

		if (empty($shopOrders))
			return '';

		$widget = new Youstice_Widgets_OrdersPage($this->language, $webReportHref, $shopName, $shopOrders, $this);

		return $widget->toString();
	}

	/**
	 * Returns html string of logo widget
	 * @param string $claim_url url to report claims form
	 * @return string html
	 */
	public function getLogoWidgetHtml($claim_url = '', $isOnOrderHistoryPage = false)
	{
		if (!trim($this->api_key))
			return '';

		if (!$this->is_properly_installed)
			return '';

		if ($isOnOrderHistoryPage)
			$claim_url .= (parse_url($claim_url, PHP_URL_QUERY) ? '&' : '?') . 'ordersPage';

		try {
			$html = $this->remote->getLogoWidgetData($this->local->getChangedReportStatusesCount(), $claim_url, $this->user_id !== null);
		} catch (Exception $e) {
			return '';
		}

		return $html;
	}

	/**
	 * Returns html string of web report button
	 * @param string $href url address where web report is created
	 * @return string of html button
	 */
	public function getWebReportButtonHtml($href)
	{
		if (!trim($this->api_key))
			return '';

		if (!$this->is_properly_installed)
			return '';

		$report = $this->local->getWebReport($this->user_id);

		//exists, just redirect
		if (!$report->canCreateNew())
		{
			$remote_link = $this->local->getCachedRemoteReportLink($report->getCode());

			if (Youstice_Tools::strlen($remote_link))
				$href = $remote_link;
		}

		$web_button = new Youstice_Widgets_WebReportButton($href, $this->language, $report);

		return $web_button->toString();
	}

	/**
	 * Returns html of product button
	 * @param string $href url address where product report is created
	 * @param integer $product_id
	 * @param integer $order_id
	 * @return string of html button
	 */
	public function getProductReportButtonHtml($href, $product_id, $order_id = null)
	{
		if (!trim($this->api_key))
			return '';

		if (!$this->is_properly_installed)
			return '';

		$report = $this->local->getProductReport($product_id, $order_id);

		//exists, just redirect
		if (!$report->canCreateNew())
		{
			$remote_link = $this->local->getCachedRemoteReportLink($report->getCode());

			if (Youstice_Tools::strlen($remote_link))
				$href = $remote_link;
		}

		$product_button = new Youstice_Widgets_ProductReportButton($href, $this->language, $report);

		return $product_button->toString();
	}

	/**
	 * Returns html of button for simple order reporting
	 * @param string $href url address where order report is created
	 * @param inteter $order_id
	 * @return string of html button
	 */
	public function getOrderReportButtonHtml($href, $order_id)
	{
		if (!trim($this->api_key))
			return '';

		if (!$this->is_properly_installed)
			return '';

		$report = $this->local->getOrderReport($order_id);

		//exists, just redirect
		if (!$report->canCreateNew())
		{
			$remote_link = $this->local->getCachedRemoteReportLink($report->getCode());

			if (Youstice_Tools::strlen($remote_link))
				$href = $remote_link;
		}

		$order_button = new Youstice_Widgets_OrderReportButton($href, $this->language, $report);

		return $order_button->toString();
	}

	/**
	 * Returns button for opening popup
	 * @param string $href url address where showing order detail is mantained
	 * @param Youstice_ShopOrder $order class with attached data
	 */
	public function getOrderDetailButtonHtml($href, Youstice_ShopOrder $order)
	{
		if (!trim($this->api_key))
			return '';

		if (!$this->is_properly_installed)
			return '';

		$products = $order->getProducts();
		$product_codes = array();

		foreach ($products as $product)
			$product_codes[] = $product->getCode();

		$report = $this->local->getOrderReport($order->getId(), $product_codes);

		$order_button = new Youstice_Widgets_OrderDetailButton($href, $this->language, $order, $report, $this);

		return $order_button->toString();
	}

	/**
	 * Returns html string of popup
	 * @param Youstice_ShopOrder $order class with attached data
	 */
	public function getOrderDetailHtml(Youstice_ShopOrder $order)
	{
		if (!trim($this->api_key))
			return '';

		if (!$this->is_properly_installed)
			return '';

		$products = $order->getProducts();
		$product_codes = array();

		foreach ($products as $product)
			$product_codes[] = $product->getCode();

		$report = $this->local->getOrderReport($order->getCode(), $product_codes);

		$order_detail = new Youstice_Widgets_OrderDetail($this->language, $order, $report, $this);

		return $order_detail->toString();
	}

	/**
	 * Action when user viewed order history (for changing report statuses count)
	 * @return Youstice_Api
	 */
	public function orderHistoryViewed()
	{
		$this->local->setChangedReportStatusesCount(0);

		return $this;
	}

	/**
	 * Creates report of web
	 * @return string where to redirect
	 */
	public function createWebReport()
	{
		$this->updateData(true);

		$local_report = $this->local->getWebReport($this->user_id);

		if ($local_report->canCreateNew())
			return $this->createWebReportExecute($this->user_id);
		else
		{
			$remote_link = $this->local->getCachedRemoteReportLink($local_report->getCode());

			if (Youstice_Tools::strlen($remote_link))
				return $remote_link;
			else
				return $this->createWebReportExecute($this->user_id);
		}
	}

	private function createWebReportExecute($user_id)
	{
		$new_code = $this->local->createWebReport($user_id, $user_id);

		$redirect_link = $this->remote->createWebReport($new_code);

		if ($redirect_link == null)
			throw new Youstice_FailedRemoteConnectionException;

		$this->setOftenUpdates();

		return $redirect_link;
	}

	/**
	 * Creates order report
	 * @param Youstice_ShopOrder $order class with attached data
	 * @return string where to redirect
	 */
	public function createOrderReport(Youstice_ShopOrder $order)
	{
		$this->updateData(true);

		$report = new Youstice_Reports_OrderReport($order->toArray());
		$local_report = $this->local->getOrderReport($report->getCode());

		if ($local_report->canCreateNew())
			return $this->createOrderReportExecute($order);
		else
		{
			$remote_link = $this->local->getCachedRemoteReportLink($local_report->getCode());

			if (Youstice_Tools::strlen($remote_link))
				return $remote_link;
			else
				return $this->createOrderReportExecute($order);
		}
	}

	private function createOrderReportExecute(Youstice_ShopOrder $order)
	{
		$report = new Youstice_Reports_OrderReport($order->toArray());
		$new_code = $this->local->createReport($report->getCode(), $this->user_id);

		$redirect_link = $this->remote->createOrderReport($order, $new_code);

		if ($redirect_link == null)
			throw new Youstice_FailedRemoteConnectionException;

		$this->setOftenUpdates();

		return $redirect_link;
	}

	/**
	 * Creates product report
	 * @param Youstice_ShopProduct $product class with attached data
	 * @return string where redirect
	 */
	public function createProductReport(Youstice_ShopProduct $product)
	{
		$this->updateData(true);

		$report = new Youstice_Reports_ProductReport($product->toArray());
		$local_report = $this->local->getProductReport($report->getCode());

		if ($local_report->canCreateNew())
			return $this->createProductReportExecute($product);
		else
		{
			$remote_link = $this->local->getCachedRemoteReportLink($local_report->getCode());

			if (Youstice_Tools::strlen($remote_link))
				return $remote_link;
			else
				return $this->createProductReportExecute($product);
		}
	}

	private function createProductReportExecute(Youstice_ShopProduct $product)
	{
		$report = new Youstice_Reports_ProductReport($product->toArray());
		$new_code = $this->local->createReport($report->getCode(), $this->user_id);

		$redirect_link = $this->remote->createProductReport($product, $new_code);

		if ($redirect_link == null)
			throw new Youstice_FailedRemoteConnectionException;

		$this->setOftenUpdates();

		return $redirect_link;
	}

	/**
	 *
	 * @param string $string to translate
	 * @param array $variables
	 * @return string translated
	 */
	public function t($string, $variables = array())
	{
		return $this->translator->t($string, $variables);
	}

	/**
	 * Create necessary table
	 * @return boolean success
	 * @throws 
	 */
	public function install()
	{
		//raise exceptions
		$this->checkIsProperlyInstalledWithExceptions();

		return $this->local->install();
	}

	/**
	 * Drop table
	 * @return boolean success
	 */
	public function uninstall()
	{
		return $this->local->uninstall();
	}

	public function setOftenUpdates()
	{
		$this->session->set('last_often_update', time());
	}

	/**
	 * Connect to remote and update local data
	 * @param boolean $force_update update also if data are acutal
	 */
	protected function updateData($force_update = false)
	{
		if ($force_update || $this->canUpdate())
		{
			if ($this->updateDataExecute())
				$this->session->set('last_update', time());
		}
	}

	/**
	 * If api key is set and time upate intervals are in range
	 * @return boolean if can update
	 */
	protected function canUpdate()
	{
		if (Youstice_Tools::strlen($this->api_key) == 0)
			return false;

		if (!$this->is_properly_installed)
			return false;

		$last_often_update = 0;
		if ($this->session->get('last_often_update'))
			$last_often_update = $this->session->get('last_often_update');

		//setOftenUpdates() was called 5 minutes before or earlier
		if ($last_often_update + $this->often_update_interval > time())
			return true;

		$last_update = 0;
		if ($this->session->get('last_update'))
			$last_update = $this->session->get('last_update');

		return $last_update + $this->update_interval < time();
	}

	/**
	 * Get data for logoWidget, update report statuses and time
	 * @return boolean success
	 */
	protected function updateDataExecute()
	{
		if (!$this->user_id)
			return false;

		$local_reports_data = $this->local->getReportsByUser($this->user_id);

		//try to get remote reports
		try {
			$remote_reports_data = $this->remote->getRemoteReportsData($local_reports_data);
		} catch (Exception $e) {
			return false;
		}

		//no new updates
		if (count($remote_reports_data) === 0)
			return true;

		$changed_report_statuses_count = $this->local->getChangedReportStatusesCount();

		foreach ($local_reports_data as $local) {
			foreach ($remote_reports_data as $remote) {
				if (!isset($remote['orderNumber']) || $local['code'] !== $remote['orderNumber'])
					continue;

				$this->local->setCachedRemoteReportLink($local['code'], $remote['redirect_link']);
				//status changed?
				if ($local['status'] !== $remote['status'])
				{
					$changed_report_statuses_count++;
					$this->local->updateReportStatus($remote['orderNumber'], $remote['status']);
				}

				$this->local->updateReportRemainingTime($remote['orderNumber'], $remote['remaining_time']);
			}
		}

		$this->local->setChangedReportStatusesCount($changed_report_statuses_count);

		return true;
	}

	/**
	 * Set database params in associative array for PDO
	 * @param array $db_credentials associative array for PDO connection with must fields: driver, host, name, user, pass
	 * @return Youstice_Api
	 */
	public function setDbCredentials(array $db_credentials)
	{
		if (count($db_credentials))
			$this->setLocal(new Youstice_Local($db_credentials));

		return $this;
	}

	/**
	 *
	 * @param Youstice_Providers_SessionProviderInterface $session
	 * @return Youstice_Api
	 */
	public function setSession(Youstice_Providers_SessionProviderInterface $session)
	{
		$this->session = $session;
		$this->session->start();

		if ($this->local !== null)
			$this->local->setSession($this->session);

		return $this;
	}

	/**
	 *
	 * @param Youstice_LocalInterface $local
	 * @return Youstice_Api
	 */
	public function setLocal(Youstice_LocalInterface $local)
	{
		$this->local = $local;

		if ($this->session !== null)
			$this->local->setSession($this->session);

		return $this;
	}

	/**
	 *
	 * @return Youstice_LocalInterface $local
	 */
	public function getLocal()
	{
		return $this->local;
	}

	/**
	 * Set eshop language
	 * @param string ISO 639-1 char code "en|sk|cz|es"
	 * @return Youstice_Api
	 * @throws InvalidArgumentException
	 */
	public function setLanguage($lang = null)
	{
		$lang = trim(Youstice_Tools::strtolower($lang));

		if ($lang && Youstice_Helpers_LanguageCodes::check($lang))
		{
			$this->language = $lang;
			$this->translator = new Youstice_Translator($this->language);
		} else
			throw new InvalidArgumentException('Language code "' . $lang . '" is not allowed.');

		return $this;
	}

	/**
	 * Set API key
	 * @param string $api_key if true api is in playground mode, data are not real
	 * @return Youstice_Api
	 */
	public function setApiKey($api_key, $use_sandbox = false)
	{
		if (!trim($api_key))
			return $this;

		$this->api_key = $api_key;

		$this->use_sandbox = ($use_sandbox == true ? true : false);

		return $this;
	}

	public function checkApiKey()
	{
		return $this->remote->checkApiKey();
	}

	/**
	 * Set what type of goods is eshop selling
	 * @param string $shop_sells "product|service"
	 * @return Youstice_Api
	 * @throws InvalidArgumentException
	 */
	public function setThisShopSells($shop_sells)
	{
		$this->shop_sells = Youstice_Tools::strtolower($shop_sells);

		return $this;
	}

	/**
	 * Check if shopSells attribute is correct
	 * @throws InvalidArgumentException
	 */
	protected function checkShopSells()
	{
		$allowed_types = array('product', 'service');

		if (in_array(Youstice_Tools::strtolower($this->shop_sells), $allowed_types))
			$this->shop_sells = Youstice_Tools::strtolower($this->shop_sells);
		else
			throw new InvalidArgumentException('Shop selling "' . $this->shop_sells . '" is not allowed.');
	}

	/**
	 * Check if curl, PDO and fileinfo are available
	 * @return boolean
	 */
	public function checkIsProperlyInstalled()
	{
		if ((!in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')) && !function_exists('curl_exec')) || !extension_loaded('PDO') || !function_exists('finfo_open') || !$this->local)
			return false;

		return true;
	}

	/**
	 * Check if curl, PDO and fileinfo are available
	 * @throws Youstice_ApiException
	 */
	public function checkIsProperlyInstalledWithExceptions()
	{
		if (!in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')) && !function_exists('curl_exec'))
			throw new Youstice_ApiException($this->t('Youstice: cURL is not installed, please install it.'), self::CURL_NOT_INSTALLED);

		if (!extension_loaded('PDO'))
			throw new Youstice_ApiException($this->t('Youstice: PDO is not installed, please install it.'), self::PDO_NOT_INSTALLED);

		if (!function_exists('finfo_open'))
			throw new Youstice_ApiException($this->t('Youstice: PECL finfo is not installed, please install it.'), self::FINFO_NOT_INSTALLED);
	}

	/**
	 * Set on which software is eshop running
	 * @param string $shop_type "prestashop|magento|ownSoftware"
	 * @param string $shop_version full version string
	 * @return Youstice_Api
	 */
	public function setShopSoftwareType($shop_type, $shop_version = '')
	{
		if (Youstice_Tools::strlen($shop_type))
			$this->shop_software_type = $shop_type;

		if (Youstice_Tools::strlen($shop_version))
			$this->shop_software_version = $shop_version;

		return $this;
	}

	/**
	 * Set user id, unique for eshop
	 * @param integer $user_id
	 * @return Youstice_Api
	 */
	public function setUserId($user_id)
	{
		$this->user_id = $user_id;

		return $this;
	}

}

class Youstice_ApiException extends Exception {
	
}

class Youstice_InvalidApiKeyException extends Exception {
	
}

class Youstice_FailedRemoteConnectionException extends Exception {
	
}

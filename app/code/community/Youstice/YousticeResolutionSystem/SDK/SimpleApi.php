<?php

/**
 * Main Youstice simple class.
 *
 * @author    Youstice
 * @copyright (c) 2015, Youstice
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 */

/**
 * Youstice main API simple class
 *
 * @author KBS Development
 */
class Youstice_Simple_Api {

	/**
	 *
	 * @var type Youstice_Translator
	 */
	protected $translator;

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
	 *
	 * @param string $language ISO 639-1 char code "en|sk|cz|es"
	 * @param string $api_key string from youstice service
	 * @param string $shop_sells "product|service"
	 * @param boolean $use_sandbox true if testing implementation
	 * @param string $shop_software_type prestashop|magento|ownSoftware
	 * @return Youstice_Simple_Api
	 */
	public static function create($language = 'sk', $api_key = '', $shop_sells = 'product', $use_sandbox = false, $shop_software_type = 'custom')
	{
		return new self($language, $api_key, $shop_sells, $use_sandbox, $shop_software_type);
	}

	/**
	 *
	 * @param string $language ISO 639-1 char code "en|sk|cz|es"
	 * @param string $api_key string from youstice service
	 * @param string $shop_sells "product|service"
	 * @param boolean $use_sandbox true if testing implementation
	 * @param string $shop_software_type prestashop|magento|ownSoftware
	 * @return Youstice_Simple_Api
	 */
	public function __construct($language = 'sk', $api_key = '', $shop_sells = 'product', $use_sandbox = false, $shop_software_type = 'custom')
	{
		$this->registerAutoloader();

		$this->setLanguage($language);
		$this->setApiKey($api_key, $use_sandbox);
		$this->setThisShopSells($shop_sells);
		$this->setShopSoftwareType($shop_software_type);

		return $this;
	}

	/**
	 * Start Youstice API
	 * @return Youstice_Simple_Api
	 */
	public function run()
	{
		$this->checkShopSells();

		$this->remote = new Youstice_Remote($this->api_key, $this->use_sandbox, $this->language, $this->shop_sells, $this->shop_software_type);

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
				require $path . '.php';
		}, true, true);  //prepend our autoloader
	}

	/**
	 * Returns html string of logo widget
	 * @param string $claims_url url to report claims form
	 * @return string html
	 */
	public function getLogoWidgetHtml($claims_url = '')
	{
		if (!trim($this->api_key))
			return '';

		return $this->remote->getLogoWidgetData(0, $claims_url, false);
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

		$report = new Youstice_Reports_WebReport();

		$web_button = new Youstice_Widgets_WebReportButton($href, $this->language, $report);

		return $web_button->toString().$this->getBaseButtonCss();
	}

	/**
	 * Creates report of web
	 * @return string where to redirect
	 */
	public function createWebReport()
	{
		return $this->createWebReportExecute();
	}

	private function createWebReportExecute()
	{
		$code = 'WEB_REPORT_SIMPLE__' . time() . substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);

		$redirect_link = $this->remote->createWebReport($code);

		if ($redirect_link == null)
			throw new Youstice_FailedRemoteConnectionException;

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
	 * Set eshop language
	 * @param string ISO 639-1 char code "en|sk|cz|es"
	 * @return Youstice_Simple_Api
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
	 * @return Youstice_Simple_Api
	 */
	public function setApiKey($api_key, $use_sandbox = false)
	{
		if (!trim($api_key))
			return $this;

		$this->api_key = $api_key;

		$this->use_sandbox = ($use_sandbox == true ? true : false);

		return $this;
	}

	/**
	 * Set what type of goods is eshop selling
	 * @param string $shop_sells "product|service"
	 * @return Youstice_Simple_Api
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
	 * Set on which software is eshop running
	 * @param string $shop_type "prestashop|magento|ownSoftware"
	 * @return Youstice_Simple_Api
	 */
	public function setShopSoftwareType($shop_type)
	{
		if (Youstice_Tools::strlen($shop_type))
			$this->shop_software_type = $shop_type;

		return $this;
	}
	
	private function getBaseButtonCss()
	{
		return '<style type="text/css">.yrsButton {
			display: inline-block;
			background: #92278f 12px 9px no-repeat;
			background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABIAAAASCAYAAABWzo5XAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2ZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo2MkM5Q0U2RjNBQkFFMzExODRGM0MxMjYyNzhBOThDRCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpGMEI5QUJDN0NGQjIxMUUzOTI5Q0VGNEJENDkwMkE1NyIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpGMEI5QUJDNkNGQjIxMUUzOTI5Q0VGNEJENDkwMkE1NyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M1LjEgV2luZG93cyI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOkNDM0RBNTM1OUZDQkUzMTE4RDFDOTBDRDQyMEFCQjlEIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjYyQzlDRTZGM0FCQUUzMTE4NEYzQzEyNjI3OEE5OENEIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+PIkovQAAAwpJREFUeNqcVF1IFFEUvndnZsednXVHwiIrRWtNpCgokB6ChQjssQezoJIkUBB1/enF1x5LV1dTaB+ip8iHSujPoqcgqIcgSAp3W38yKdnZ2Z3dWWdm56dzJxVR14cuXObeO+d83z3nfOfiyNEwKjYs00LdsV6/ls43RM9E39iWjRDe2daFdhmheB8lJVLPWYGbbvvUEeya7cHFbHcFgmHTDP1Oy6iI8dGxcPVd+3+BaGQjhuYYsqZ2MywKRPIxWj+iG4YxQDEUkhbSAz1z/WxRRuIACSVs1vDhQRsGoCAETl4lqQS5PRxKxUVEe5i2e8ci7R1fu1wQooUxdq4BecTOhcDRk0qkXmWX5P7xE2NkXw6zmkwxJi6Is0nbVPUGwiDNpcbgcwBmJUxh/Pgoyiymb4P/I7RuRIYqq7aW1Ww9r9uWZTln8lKmExhLxVhynuxNw7S1nObYqRl13dVGkbow0uVCUFlRmtKLaecwPS9F4BOAWQ/hIAiZ3JQH6DpLt0+L8eQMsRPj4kdFzDcqmfw5FKl1BEniZOEfC8wJwqj8yV2GM2YkMOSQQV7Q8JFBJCWkZyBUIEvfWSsWKSnGm5Vt6ibCLoxa3rZOCtVCU24l1+Tb55sCkAIkn4FcPBaqhItaKt/sKeefAkmB2G8rP+WmUOhHXwnDMVNkz+/lPxOQUKKvBrYcRbtmCvkCip6NTkK4GyDFdFTQFf1mKiaugg7469M3JDj7AbeZcPP0B7fXjVrft18CQldRQRJNQagmSCTIV/g8EPoXhO1k9pfcw5ayVxiOfUmyWZBXr63lZgcgMCDC7PweqvNXCsiAEKD0Aw/PPwiUHvRHQZje7HJuyjZtZJpm40jtkGYZ1nYgQzUQSKc+u5z9Bh2f5cq9Jf5DwiDJG1RNgZzky2rKmlcz6gUgoq++aEl3x3vZdbANoN75Wy5NUqt8FT6EafwEWoCCMHWnFaBlSL9BlbT7p8Zfyz/l3/x+3g9uXrT2HuCtDxuEVjNxcixBqrO5KlseOzcsfZFAWMTUP5u/AgwAXbTAlNaE4y8AAAAASUVORK5CYII=);
			color: #fff;
			text-decoration: none;
			font-family: Arial, Helvetica, sans-serif;
			font-size: 16px;
			font-size: 1.2em;
			padding: 10px 21px 10px 42px;
			font-weight: normal;
			line-height: 1em;

			border: 1px solid #3c193a;
			border-radius: 7px;
			-moz-border-radius: 7px;
			white-space:nowrap;
		}

		.yrsButton:hover { text-decoration: none; color: #fff; }

		.yrsButton:active, .yrsButton:visited { color: #fff; }
		</style>';
	}

}

class Youstice_InvalidApiKeyException extends Exception {
	
}

class Youstice_FailedRemoteConnectionException extends Exception {
	
}

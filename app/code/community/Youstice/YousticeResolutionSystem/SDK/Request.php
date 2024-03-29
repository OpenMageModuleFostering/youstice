<?php
/**
 * Base communication interface
 *
 * @author    Youstice
 * @copyright (c) 2015, Youstice
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 */

class Youstice_Request {

	private $auth_login = 'adminapi';
	private $auth_passw = 'AdminApi';
	protected $response = null;
	private $additional_params = array();

	public function returnResponse()
	{
		return $this->response;
	}

	public function responseToArray()
	{
		$data = Youstice_Tools::jsonDecode($this->response, true);

		return $data;
	}

	public function setAdditionalParam($key, $val)
	{
		$this->additional_params[$key] = $val;
	}

	protected function generateUrl($url)
	{
		$api_url = $this->use_sandbox ? $this->api_sandbox_url : $this->api_url;

		$return_url = $api_url.$url.'/'.$this->api_key.'?version=1&channel='.$this->shop_software_type;

		if (count($this->additional_params))
		{
			foreach ($this->additional_params as $key => $val) {
				$return_url .= '&' . urlencode($key) . '=' . urlencode($val);
			}
			
			//reset params for next calls
			$this->additional_params = array();
		}

		return $return_url;
	}

	public function post($url, $data = array())
	{
		$url = $this->generateUrl($url);
		$this->postStream($url, $data);

		if ($this->response === false || $this->response === null || strpos($this->response, "HTTP Status 500") !== false) {
			$this->logError($url, "POST", $data, $this->response);
			
			throw new Youstice_FailedRemoteConnectionException('Post Request failed: '.$url);
		}

		if (strpos($this->response, 'Invalid api key') !== false)
			throw new Youstice_InvalidApiKeyException;

		return $this->response;
	}

	public function get($url)
	{
		$url = $this->generateUrl($url);
		$this->getStream($url);

		if ($this->response === false || $this->response === null || strpos($this->response, "HTTP Status 500") !== false) {
			
			$this->logError($url, "GET", array(), $this->response);
			
			throw new Youstice_FailedRemoteConnectionException('get Request failed: '.$url);
		}

		if (strpos($this->response, 'Invalid api key') !== false)
			throw new Youstice_InvalidApiKeyException;

		return $this->response;
	}

	protected function getStream($url)
	{
		$request = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'ignore_errors' => true,
				'timeout' => 10.0,
				'header' => "Content-Type: application/json\r\n".'Accept-Language: '.$this->lang."\r\n",
			)
		));

		$url = str_replace('://', '://'.$this->auth_login.':'.$this->auth_passw.'@', $url);

		$this->response = Youstice_Tools::file_get_contents($url, false, $request);
	}

	protected function postStream($url, $data)
	{
		$request = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'ignore_errors' => true,
				'timeout' => 10.0,
				'content' => Youstice_Tools::jsonEncode($data),
				'header' => "Content-Type: application/json\r\n".'Accept-Language: '.$this->lang."\r\n",
			)
		));

		$url = str_replace('://', '://'.$this->auth_login.':'.$this->auth_passw.'@', $url);

		$this->response = Youstice_Tools::file_get_contents($url, false, $request);
	}
	
	protected function logError($url, $type, $data, $response)
	{
		error_log("Youstice - remote request failed [url]: " . $type . " " . $url . " [request]: " . json_encode($data) . " [response]: " . $response);
	}

}


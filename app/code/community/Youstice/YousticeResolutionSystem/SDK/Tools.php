<?php

class Youstice_Tools {

	public static function strtolower($str)
	{
		if (function_exists('mb_strtolower'))
			return mb_strtolower($str, 'utf-8');

		return strtolower($str);
	}

	public static function strlen($str, $encoding = 'UTF-8')
	{
		$str = html_entity_decode($str, ENT_COMPAT, 'UTF-8');

		if (function_exists('mb_strlen'))
			return mb_strlen($str, $encoding);

		return strlen($str);
	}

	public static function file_get_contents($url, $use_include_path = false, $stream_context = null, $curl_timeout = 5)
	{
		if ($stream_context == null && preg_match('/^https?:\/\//', $url))
			$stream_context = @stream_context_create(array('http' => array('timeout' => $curl_timeout)));
		if (in_array(ini_get('allow_url_fopen'), array('On', 'on', '1')) || !preg_match('/^https?:\/\//', $url))
			return @file_get_contents($url, $use_include_path, $stream_context);
		elseif (function_exists('curl_init'))
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($curl, CURLOPT_TIMEOUT, $curl_timeout);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			if ($stream_context != null)
			{
				$opts = stream_context_get_options($stream_context);
				if (isset($opts['http']['method']) && Youstice_Tools::strtolower($opts['http']['method']) == 'post')
				{
					curl_setopt($curl, CURLOPT_POST, true);
					if (isset($opts['http']['content']))
					{
						parse_str($opts['http']['content'], $datas);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $datas);
					}
				}
			}
			$content = curl_exec($curl);
			curl_close($curl);
			return $content;
		} else
			return false;
	}

	public static function jsonDecode($json, $assoc = false)
	{
		return json_decode($json, $assoc);
	}

	public static function jsonEncode($json, $assoc = false)
	{
		return json_encode($json, $assoc);
	}

}

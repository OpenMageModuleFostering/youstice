<?php
/**
 * Represents one web report.
 *
 * @author    Youstice
 * @copyright (c) 2015, Youstice
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 */

class Youstice_Reports_WebReport extends Youstice_Reports_BaseReport {

	public function getCode()
	{
		if (count($this->data) && isset($this->data['code']))
			return $this->data['code'];

		return 'WEB_REPORT__';
	}

	public function setCode($user_id)
	{
		$this->data['code'] = 'WEB_REPORT__'.$user_id;
	}

}

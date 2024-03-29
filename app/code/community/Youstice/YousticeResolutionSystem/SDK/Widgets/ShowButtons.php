<?php
/**
 * Youstice show buttons widget.
 *
 * @author    Youstice
 * @copyright (c) 2015, Youstice
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 */

class Youstice_Widgets_ShowButtons {

	protected $href;
	protected $has_reports;
	protected $translator;

	public function __construct($lang, $has_reports)
	{
		$this->has_reports = $has_reports;
		$this->translator = new Youstice_Translator($lang);
	}

	public function toString()
	{
		$text = $this->translator->t('Would you like to file a complaint?');

		return '<a href="#" class="yrsShowButtons yrsButton" 
					data-has-reports="'.(int)$this->has_reports.'">'
				.$text.'</a>';
	}

}

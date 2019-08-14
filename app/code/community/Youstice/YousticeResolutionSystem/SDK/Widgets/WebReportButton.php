<?php
/**
 * Renders the "unrelated to orders" report button (web report / generic claim)
 *
 * @author    Youstice
 * @copyright (c) 2015, Youstice
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 */

class Youstice_Widgets_WebReportButton {

	protected $href;
	protected $translator;
	protected $report;

	public function __construct($href, $lang, Youstice_Reports_WebReport $report)
	{
		$this->href = $href;
		$this->translator = new Youstice_Translator($lang);
		$this->report = $report;
	}

	public function toString()
	{
		if (!$this->report->exists())
			return $this->renderUnreportedButton();

		if ($this->report->getRemainingTime() == 0)
			return $this->renderReportedButton();
		else
			return $this->renderReportedButtonWithTimeString();
	}

	protected function renderReportedButton()
	{
		$status = $this->report->getStatus();
		$status_css_class = '';
		if ($status == 'Problem reported')
			$status_css_class = 'yrsButton-problem-reported';

		$message = $this->translator->t($status);

		$output = '<a class="yrsButton '.$status_css_class.'" target="_blank" 
					href="'.Youstice_Helpers_HelperFunctions::sh($this->href).'">'.Youstice_Helpers_HelperFunctions::sh($message).'</a>';

		return $output;
	}

	protected function renderReportedButtonWithTimeString()
	{
		$message = $this->translator->t($this->report->getStatus());
		$remaining_time_string = Youstice_Helpers_HelperFunctions::remainingTimeToString($this->report->getRemainingTime(), $this->translator);

		$output = '<a class="yrsButton yrsButton-with-time" target="_blank" 
					href="'.Youstice_Helpers_HelperFunctions::sh($this->href).'">
					<span>'.Youstice_Helpers_HelperFunctions::sh($message).'</span>
					<span>'.Youstice_Helpers_HelperFunctions::sh($remaining_time_string).'</span></a>';

		return $output;
	}

	protected function renderUnreportedButton()
	{
		$message = $this->translator->t('Report a problem unrelated to your orders');

		$output = '<a class="yrsButton" target="_blank" 
					href="'.Youstice_Helpers_HelperFunctions::sh($this->href).'">'.Youstice_Helpers_HelperFunctions::sh($message).'</a>';

		return $output;
	}

}

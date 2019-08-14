<?php
/**
 * Renders button to report a product
 *
 * @author    Youstice
 * @copyright (c) 2014, Youstice
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 */

class Youstice_Widgets_ProductReportButton {

	protected $href;
	protected $translator;
	protected $report;

	public function __construct($href, $lang, Youstice_Reports_ProductReport $report)
	{
		$this->href = $href;
		$this->translator = new Youstice_Translator($lang);
		$this->report = $report;
	}

	public function toString()
	{
		if ($this->report->exists())
		{
			if ($this->report->getRemainingTime() == 0)
				return $this->renderReportedButton();

			return $this->renderReportedButtonWithTimeString();
		}

		return $this->renderUnreportedButton();
	}

	protected function renderReportedButton()
	{
		$status = $this->report->getStatus();
		$status_css_class = 'yrsButton-'.Youstice_Helpers_HelperFunctions::webalize($status);

		$message = $this->translator->t($status);

		$output = '<a class="yrsButton '.$status_css_class.'" target="_blank" 
					href="'.Youstice_Helpers_HelperFunctions::sh($this->href).'">'.Youstice_Helpers_HelperFunctions::sh($message).'</a>';

		return $output;
	}

	protected function renderReportedButtonWithTimeString()
	{
		$status = $this->report->getStatus();
		$message = $this->translator->t($status);
		$status_css_class = 'yrsButton-'.Youstice_Helpers_HelperFunctions::webalize($status);
		$remaining_time_string = Youstice_Helpers_HelperFunctions::remainingTimeToString($this->report->getRemainingTime(), $this->translator);

		$output = '<a class="yrsButton yrsButton-with-time '.$status_css_class.'" target="_blank" 
					href="'.Youstice_Helpers_HelperFunctions::sh($this->href).'">
					<span>'.Youstice_Helpers_HelperFunctions::sh($message).'</span>
					<span>'.Youstice_Helpers_HelperFunctions::sh($remaining_time_string).'</span></a>';

		return $output;
	}

	protected function renderUnreportedButton()
	{
		$message = $this->translator->t('Report a problem');

		$output = '<a class="yrsButton" target="_blank" 
					href="'.Youstice_Helpers_HelperFunctions::sh($this->href).'">'.Youstice_Helpers_HelperFunctions::sh($message).'</a>';

		return $output;
	}

}

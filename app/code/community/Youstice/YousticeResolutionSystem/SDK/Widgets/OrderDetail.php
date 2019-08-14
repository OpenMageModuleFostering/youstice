<?php
/**
 * Renders order detail (usually in popup form)
 *
 * @author    Youstice
 * @copyright (c) 2015, Youstice
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 */

class Youstice_Widgets_OrderDetail {

	protected $api;
	protected $lang;
	protected $report;
	protected $order;

	public function __construct($lang, Youstice_ShopOrder $order, Youstice_Reports_OrderReport $report, $api)
	{
		$this->translator = new Youstice_Translator($lang);
		$this->order = $order;
		$this->report = $report;
		$this->api = $api;
	}

	public function toString()
	{
		$products = $this->order->getProducts();
		$output = '<div class="orderDetailWrap"><h1>'.$this->translator->t('Order').' '.Youstice_Helpers_HelperFunctions::sh($this->order->getName()).'</h1>';
		$output .= '<div class="topRightWrap">';
		$output .= $this->api->getOrderReportButtonHtml($this->order->getHref(), $this->order->getCode());
		$output .= '<span class="space"></span>
					<a class="yrsButton yrsButton-close">x</a>
					</div>
					<h2>'.$this->translator->t('Products in your order (%d)', count($products)).'</h2>';

		if (count($products))
		{
			$output .=
					'<table class="orderDetail">';

			$products = $this->order->getProducts();

			foreach ($products as $product)
			{
				$output .= '<tr><td>'.Youstice_Helpers_HelperFunctions::sh($product->getName()).'</td>
							<td>'.$this->api->getProductReportButtonHtml($product->getHref(), $product->getId(), $product->getOrderId()).'</td></tr>';
			}

			$output .= '</table></div>';
		}

		return $output;
	}

}

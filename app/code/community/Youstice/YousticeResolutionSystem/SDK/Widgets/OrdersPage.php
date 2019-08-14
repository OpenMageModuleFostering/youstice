<?php
/**
 * Renders order detail (usually in popup form)
 *
 * @author    Youstice
 * @copyright (c) 2014, Youstice
 * @license   http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 */

class Youstice_Widgets_OrdersPage {

	protected $api;
	protected $lang;
	protected $webReportHref;
	protected $shopName;
	protected $orders;

	public function __construct($lang, $webReportHref, $shopName, array $orders, $api)
	{
		$this->translator = new Youstice_Translator($lang);
		$this->webReportHref = $webReportHref;
		$this->shopName = $shopName;
		$this->orders = $orders;
		$this->api = $api;
	}

	public function toString()
	{
		$output = '<div class="orderDetailWrap ordersPageWrap"><h1>'.$this->translator->t('Report claims on').' '.Youstice_Helpers_HelperFunctions::sh($this->shopName).'</h1>';
		$output .= '<div class="topRightWrap">';
		$output .= $this->api->getWebReportButtonHtml($this->webReportHref);
		$output .= '<span class="space"></span>
					<a class="yrsButton yrsButton-close">x</a>
					</div>
					<h2>'.$this->translator->t('Your orders (%d)', count($this->orders)).'</h2>';

		if (count($this->orders))
		{
			$output .=
					'<table class="orderDetail">';

			$i = 0;
			foreach ($this->orders as $order)
			{
				$paymentText = $this->api->t($order->isPaid() ? 'paid' : 'unpaid');
				$deliveryText = $this->api->t($order->isDelivered() ? 'delivered' : 'undelivered');
				$orderDateLabel = $this->translator->t('Order date');
				$orderDate = strtotime($order->getOrderDate());
				$orderDateFormat = $this->translator->t('_orderDateFormat');
				$orderDateFormated = Youstice_Helpers_HelperFunctions::sh(date($orderDateFormat, $orderDate));
				
				$totalLabel = $this->translator->t('Total');
				$totalText = Youstice_Helpers_HelperFunctions::sh($order->getPrice()).' '.Youstice_Helpers_HelperFunctions::sh($order->getCurrency());
				
				$output .= '<tr><td>'
						. '<b>'.Youstice_Helpers_HelperFunctions::sh($order->getName()).'</b>'
						. ' (' . $paymentText . ', ' . $deliveryText . ')<br>'
						. $orderDateLabel.': '.$orderDateFormated.'<br>'
						. $totalLabel.': '.$totalText.'</td>'
						. '<td>'.$this->api->getOrderDetailButtonHtml($order->getOrderDetailHref(), $order).'</td></tr>';
				
				$i++;
			}

			$output .= '</table></div>';
		}

		return $output;
	}

}


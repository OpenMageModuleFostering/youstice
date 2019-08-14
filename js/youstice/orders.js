
function showAjaxSpinner(where) {
    jQuery(where).append('<div class="y-ajax-spinner"></div>');
}

function removeAjaxSpinner(where) {
    jQuery(where).find('.y-ajax-spinner').remove();
}

jQuery(document).ready(function($) {

    //button to start showing buttons
    $('h1').after('<div id="y-main" \>');
    showAjaxSpinner('#y-main');
    $.get(BASE_URL + 'index/getShowButtonsHtml', function(data) {
	removeAjaxSpinner('#y-main');
	$('#y-main').append(data);

	if ($(data).data('has-reports'))
	    showButtons();
    });

    //start showing buttons
    $(document).on('click', 'a.yrsShowButtons', function(e) {
        e.preventDefault();
        showButtons();
    });

    function showButtons() {
        $('a.yrsShowButtons').remove();
	showAjaxSpinner('#y-main');
	
	$.get(BASE_URL + 'index/getWebReportButton', function(data) {
	    removeAjaxSpinner('#y-main');
	    $('#y-main').append(data);
	});

	showOrdersButtons();
    }
    
    function showOrdersButtons() {
	var orderIds = [];

	$('#my-orders-table tr:gt(0)').each(function() {
	    var anchor = $(this).find('td a:first-of-type');
	    var href = anchor.attr('href');
	    var hrefParts = href.split('/');

	    var orderId = hrefParts[hrefParts.length - 2];

	    $(this).find('td:first').append('<div id="yousticeOrderButton-' + orderId + '" />');
	    showAjaxSpinner('#yousticeOrderButton-' + orderId);

	    if (!isNaN(orderId))
		orderIds.push(orderId);
	});

	$.get(BASE_URL + 'index/getOrdersButtons', {"order_ids": orderIds}, function(data) {
	    for (key in data) {
		$('#yousticeOrderButton-' + key).html(data[key]);
	    }

	    $('.yrsButton-plus, .yrsOrderDetailButton, .yrsButton-order-detail').click(function(e) {
		$this = $(this);
		e.preventDefault();
		$.fancybox({
		    autoDimension: true,
		    href: $this.attr('href'),
		    type: 'ajax',
		    closeBtn: false
		});
		return false;
	    });
	}, 'json');
    }

    //reload orderDetail
    $(document).on('click', '.yrsButton:not(.yrsButton-order-detail):not(.yrsOrderDetailButton)'
	    + ':not(.yrsButton-plus):not(.yrsButton-close):not(.yrsShowButtons)', function(e) {
		setTimeout(function() {
		    window.location.reload();
		}, 300);
	    });

    //hide orderDetail
    $(document).on('click', '.yrsButton-close', function(e) {
	e.preventDefault();
	$.fancybox.close();
    });
});
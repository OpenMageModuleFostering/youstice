jQuery(document).ready(function($) {
    var currentLink = window.location.href;
    var linkParts = currentLink.split('/');
    var orderId = linkParts[linkParts.length - 2];
    
    $('#my-orders-table tr:gt(0)').each(function() {
        var firstTd = $(this).find('td:first-of-type');
        //not tfoot, only tbody
        if(firstTd.find('h3').length === 0)
            return true;
        var sku = firstTd.next('td').html();
        $('<div id="yousticeProductButton-'+sku+'" />').appendTo(firstTd);
	showAjaxSpinner('#yousticeProductButton-'+sku);
    });
    
    $.getJSON(BASE_URL + 'index/getProductsButtons', {"order_id": orderId}, function(data) {
        for (key in data) {
            $('#yousticeProductButton-' + key).html(data[key]);
        }
    });

});

function showAjaxSpinner(where) {
    jQuery(where).append('<div class="y-ajax-spinner"></div>');
}
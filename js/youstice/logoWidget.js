jQuery(document).ready(function($) {
    
    //show logoWidget
    $.get(BASE_URL + 'index/getLogoWidget', function(data) {
	$('body').append(data);
    });
});
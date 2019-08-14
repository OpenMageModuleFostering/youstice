jQuery(document).ready(function($) {

    $('input[name="have_account"]').change(function() {
	changeBlocksVisibility($(this).val() == 1);
    });

    $('select#useSandbox').change(function() {
	changeSandboxText();
    });

    function changeSandboxText() {
	if ($('select#useSandbox').val() == 1) {
	    $('.row.onSandbox').show();
	}
	else {
	    $('.row.onSandbox').hide()
	}
    }

    changeSandboxText();
    changeBlocksVisibility($('input[name="have_account"]').val() == 1);

    $('#yGetApiKey').click(function(e) {
	e.preventDefault();
	var win;
	if ($('#useSandbox').val() == 1) {
	    win = window.open(sandUrl, '_blank');
	}
	else {
	    win = window.open(liveUrl, '_blank');
	}

	win.focus();
    });

    $('.yBlock.screenshots a[rel="screenshotRemote"]').fancybox();
    $('.yBlock.howItWorks a[rel="screenshot"]').fancybox();
    
    jQuery('a.save').click(function(e) {
	e.preventDefault();
	saveSettings();
    });
});

function saveSettings() {
    jQuery('.messages').remove();
    jQuery.post(checkApiKeyUrl, {form_key: window.FORM_KEY, api_key: jQuery('#apiKey').val(), use_sandbox: jQuery('#useSandbox').val()},
    function(response) {
	if (response.result !== true) {
	    showError();
	    jQuery('html, body').animate({
		scrollTop: jQuery(".yConfiguration").first().offset().top
	    }, 2000);
	}
	else {
	    jQuery('.yContainer').submit();
	}
    }, 'json');
}

function changeBlocksVisibility(haveAccount) {
    jQuery('.yBlock, .yConfiguration').show();
    if (haveAccount) {
	jQuery('.yBlock.screenshots, .yBlock.stopScathingReviews').hide();
    }
    else {
	jQuery('.yConfiguration').hide();
    }
}

function showError() {
    jQuery('.roundedAnchor.save').after(errorHtml);
    jQuery('html, body').animate({
	scrollTop: jQuery(".yConfiguration").first().offset().top
    }, 2000);
}
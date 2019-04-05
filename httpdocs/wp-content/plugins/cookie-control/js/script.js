jQuery(document).ready(function($) {
    
    $( "#cookie-tabs" ).tabs();
    	
    $('.addRow').on('click',function (e) {
        
	e.preventDefault();        
        
        var containerClass = $(this).data('class');
        
        $('<div/>', {
               'class' : containerClass, html: GetHtml(containerClass)
        }).hide().appendTo('#' + containerClass + 'Container').slideDown('slow');//Get the html from template and hide and slideDown for transtion.

    });
    	
    $('body').on('click', '.removeRow', function(e){ 
        e.preventDefault();
        
        var containerClass = $(this).data('class');
        
        $(this).parents('td').parents('tr').parents('table').parents('.' + containerClass).remove();
       		
    });
    
});

//Get the template and update the input field names 
function GetHtml(containerClass) {
    
    var len = parseInt(jQuery('#last-used-key-' + containerClass).attr('data-keyid')) + 1;
    jQuery('#last-used-key-' + containerClass).attr('data-keyid', len);
    
    var $html = jQuery('.' + containerClass + 'Template').clone(); 
	
    if ( containerClass === 'altLanguages') {
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'Text\\]').prop('name', 'cookiecontrol_settings[' + containerClass + 'Text][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'Text\\]').prop('id', 'cookiecontrol_settings[' + containerClass + 'Text][' + len + ']');
    }
    
    if ( containerClass === 'optionalCookies') {
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'Name\\]').prop('name', 'cookiecontrol_settings[' + containerClass + 'Name][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'Name\\]').prop('id', 'cookiecontrol_settings[' + containerClass + 'Name][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'Label\\]').prop('name', 'cookiecontrol_settings[' + containerClass + 'Label][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'Label\\]').prop('id', 'cookiecontrol_settings[' + containerClass + 'Label][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'Description\\]').prop('name', 'cookiecontrol_settings[' + containerClass + 'Description][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'Description\\]').prop('id', 'cookiecontrol_settings[' + containerClass + 'Description][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'Array\\]').prop('name', 'cookiecontrol_settings[' + containerClass + 'Array][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'Array\\]').prop('id', 'cookiecontrol_settings[' + containerClass + 'Array][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'onAccept\\]').prop('name', 'cookiecontrol_settings[' + containerClass + 'onAccept][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'onAccept\\]').prop('id', 'cookiecontrol_settings[' + containerClass + 'onAccept][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'onRevoke\\]').prop('name', 'cookiecontrol_settings[' + containerClass + 'onRevoke][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'onRevoke\\]').prop('id', 'cookiecontrol_settings[' + containerClass + 'onRevoke][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'initialConsentState\\]').prop('name', 'cookiecontrol_settings[' + containerClass + 'initialConsentState][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'initialConsentState\\]').prop('id', 'cookiecontrol_settings[' + containerClass + 'initialConsentState][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'thirdPartyCookies\\]').prop('name', 'cookiecontrol_settings[' + containerClass + 'thirdPartyCookies][' + len + ']');
        $html.find('#cookiecontrol_settings\\[' + containerClass + 'thirdPartyCookies\\]').prop('id', 'cookiecontrol_settings[' + containerClass + 'thirdPartyCookies][' + len + ']');
    }
	
    $html.find('#cookiecontrol_settings\\[' + containerClass + '\\]').prop('name', 'cookiecontrol_settings[' + containerClass + '][' + len + ']');
    $html.find('#cookiecontrol_settings\\[' + containerClass + '\\]').prop('id', 'cookiecontrol_settings[' + containerClass + '][' + len + ']');
    
    return $html.html();    
}


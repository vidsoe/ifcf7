var ifcf7_utilities = {

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    fix_mobile_numbers: function(){
        jQuery('input[type="number"]').attr('pattern', '[0-9]*');
    },

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	load: function(){
		jQuery('.wpcf7-form').on({
			wpcf7mailsent: ifcf7_utilities.wpcf7mailsent,
			wpcf7reset: ifcf7_utilities.wpcf7reset,
            wpcf7submit: ifcf7_utilities.wpcf7submit,
		});
        jQuery('.wpcf7-form *').on('keydown', function(event){
			event.stopPropagation();
			switch(event.which){
				case 13: // Enter
					if(!jQuery(this).is('textarea')){
						event.preventDefault();
					}
					break;
				case 32: // Space character
					if(jQuery(this).is('.wpcf7-submit')){
						event.preventDefault();
					}
					break;
			}
		});
        jQuery('.wpcf7-submit').on('click', function(){
			jQuery(this).addClass('disabled');
		});
	},

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	redirect_url: '',

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	thank_you_message: '',

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    wpcf7mailsent: function(event){
        ifcf7_utilities.redirect_url = event.detail.apiResponse.redirect_url;
        ifcf7_utilities.thank_you_message = event.detail.apiResponse.thank_you_message;
        if('' !== ifcf7_utilities.redirect_url || '' !== ifcf7_utilities.thank_you_message){
            if('' !== event.detail.apiResponse.loading_message){
                jQuery('#' + event.detail.unitTag + ' .wpcf7-form').children().hide();
                jQuery('#' + event.detail.unitTag + ' .wpcf7-form').children().removeClass('d-block d-flex d-inline d-inline-block d-inline-flex d-table d-table-cell d-table-row').addClass('d-none');
                jQuery('#' + event.detail.unitTag + ' .wpcf7-form').prepend('<div class="alert alert-info if-cf7-response-output" role="alert"><span class="if-cf7-response-message">' + event.detail.apiResponse.loading_message + '</span></div>');
            }
        }
    },

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    wpcf7reset: function(event){
        if('' !== ifcf7_utilities.thank_you_message){
            if(jQuery('#' + event.detail.unitTag + ' .if-cf7-response-output').length){
                jQuery('#' + event.detail.unitTag + ' .if-cf7-response-message').text(ifcf7_utilities.thank_you_message);
            } else {
                jQuery('#' + event.detail.unitTag + ' .wpcf7-form').children().hide();
                jQuery('#' + event.detail.unitTag + ' .wpcf7-form').children().removeClass('d-block d-flex d-inline d-inline-block d-inline-flex d-table d-table-cell d-table-row').addClass('d-none');
                jQuery('#' + event.detail.unitTag + ' .wpcf7-form').prepend('<div class="alert alert-info if-cf7-response-output" role="alert"><span class="if-cf7-response-message">' + ifcf7_utilities.thank_you_message + '</span></div>');
            }
        }
        if('' !== ifcf7_utilities.redirect_url){
            if(jQuery('#' + event.detail.unitTag + ' .if-cf7-response-output').length){
                jQuery('#' + event.detail.unitTag + ' .if-cf7-response-output').append('<span class="ajax-loader float-right m-0 visible"></span>');
            }
            setTimeout(function(){
                jQuery(location).attr('href', ifcf7_utilities.redirect_url);
            }, 600);
        }
    },

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    wpcf7submit: function(event){
        jQuery('#' + event.detail.unitTag + ' .wpcf7-submit').removeClass('disabled');
    },

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

};

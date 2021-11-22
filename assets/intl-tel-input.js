var ifcf7_intl_tel_input = {

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    fields: {},

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	load: function(){
		jQuery(function(){
            jQuery('.ifcf7-intl-tel-input').each(function(index, element){
                var name = jQuery(element).attr('name');
                ifcf7_intl_tel_input.fields[name] = window.intlTelInput(element, {
        			preferredCountries: ['gt'],
        		  	utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js',
        		});
            });
            jQuery('.ifcf7-intl-tel-input').on('input propertychange', function(){
                var name = jQuery(this).attr('name');
                var phone_number = ifcf7_intl_tel_input.fields[name].getNumber();
                jQuery(this).next('.ifcf7-intl-tel-input-hidden').val(phone_number);
            });
		});
	},

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

};

var ifcf7_uploaded_files = {

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	load: function(){
		jQuery(function($){
            jQuery('.ifcf7-delete-attachment').on('click', function(event){
                event.preventDefault();
                if(confirm('Are you sure you want to delete these file?')){
                    var attachment_container = $(this).parents('.ifcf7-attachment-container');
                    var attachments_container = $(this).parents('.ifcf7-attachments-container');
                    var meta_key = attachments_container.data('ifcf7-key');
                    var meta_type = attachments_container.data('ifcf7-type');
                    var meta_value = attachment_container.data('ifcf7-id');
                    var nonce = $(this).data('ifcf7-nonce');
                    var object_id = attachments_container.data('ifcf7-id');
                    attachment_container.css({
                        opacity: 0.5,
                    });
                    $(this).text('Deleting...');
                    $.ajax({
                        beforeSend: function(xhr){
                            xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                        },
                        data:{
                            'ifcf7_nonce': nonce,
                        },
                        method: 'DELETE',
                        url: wpApiSettings.root + 'ifcf7/v1/uploaded-files/' + meta_type + '/' + object_id + '/' + meta_key + '/' + meta_value,
                    }).done(function(response){
                        if('undefined' !== typeof(response.ifcf7_status)){
                            if(1 === response.ifcf7_status){
                                attachment_container.remove();
                            } else {
                                alert('File could not be deleted due to an error.');
                            }
                        } else {
                            alert('Unknown error.');
                        }
                    }).fail(function(response){
                        if('undefined' !== typeof(response.responseJSON)){
                            alert(response.responseJSON.message);
                        } else {
                            alert('Something went wrong.');
                        }
                    });
                }
            });
		});
	},

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

};

<?php namespace IFCF7;

final class Storage {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static $inserted_post_id = 0, $inserted_user_id = 0, $updated_post_id = 0, $updated_user_id = 0;

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function get_post_type($contact_form = null){
        $type = $contact_form->pref('post_type');
        if(empty($type)){
            return 'ifcf7-submission';
        }
        if(!post_type_exists($type)){
            return 'ifcf7-submission';
        }
        return $type;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function get_post_status($contact_form = null){
        $status = $contact_form->pref('post_status');
        if(empty($status)){
            return 'private';
        }
        $statuses = get_post_statuses();
        if(empty($statuses[$status])){
            return 'private';
        }
        return $status;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function update_response($response = '', $status = ''){
        $post_id = 0;
		$user_id = 0;
		if(!empty(self::$inserted_post_id)){
			$post_id = self::$inserted_post_id;
		} elseif(!empty(self::$updated_post_id)){
			$post_id = self::$updated_post_id;
		} elseif(!empty(self::$inserted_user_id)){
			$user_id = self::$inserted_user_id;
		} elseif(!empty(self::$updated_user_id)){
			$user_id = self::$updated_user_id;
		}
        if(!empty($post_id)){
            update_post_meta($post_id, 'submission_response', $response);
            update_post_meta($post_id, 'submission_status', $status);
        } elseif(!empty($user_id)){
            update_user_meta($user_id, 'submission_response', $response);
            update_user_meta($user_id, 'submission_status', $status);
        }
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function form_tag_html($html, $tag, $type, $basetype, $html_orig){
		if('file' !== $type){
			return $html;
		}
        $contact_form = wpcf7_get_current_contact_form();
    	if(null === $contact_form){
    		return $html;
    	}
        $meta_type = '';
    	$object_id = 0;
    	$post_id = $contact_form->shortcode_attr('post_id');
    	if(!empty($post_id)){
    		$meta_type = 'post';
    		$object_id = Edit_Post::sanitize_post_id($post_id);
    	}
    	$user_id = $contact_form->shortcode_attr('user_id');
    	if(!empty($user_id)){
    		$meta_type = 'user';
    		$object_id = Edit_User::sanitize_user_id($user_id);
    	}
    	if(!in_array($meta_type, ['post', 'user'])){
    		return $html;
    	}
    	if(empty($object_id)){
    		return $html;
    	}
        $attachment_ids = get_metadata($meta_type, $object_id, $tag->name . '_id');
        if(empty($attachment_ids)){
    		return $html;
    	}
        $html_orig = $html;
        $html = '<div class="ifcf7-attachments-container" data-ifcf7-key="' . $tag->name . '" data-ifcf7-id="' . $object_id . '" data-ifcf7-message="' . __('Deleting...') . '" data-ifcf7-type="' . $meta_type . '">';
    	foreach($attachment_ids as $attachment_id){
            $metadata = wp_prepare_attachment_for_js($attachment_id);
    		$html .= '<div class="border-top d-flex ifcf7-attachment-container mb-3 pt-3" data-ifcf7-id="' . $attachment_id . '">';
            $url = $metadata['icon'];
            if('image' === $metadata['type'] and !empty($metadata['sizes']['thumbnail'])){
                $url = $metadata['sizes']['thumbnail']['url'];
            }
    		$html .= '<img class="img-thumbnail" src="' . $url . '" style="width: 60px;">';
    		$html .= '<div class="flex-grow-1 pl-3">';
    		$html .= '<div class="text-dark"><a href="' . $metadata['url'] . '" target="_blank">' . $metadata['name'] . '</a></div>';
    		$html .= '<div class="small text-muted">' . $metadata['dateFormatted'] . ' &bull; ' . $metadata['filesizeHumanReadable'];
    		if(!empty($metadata['nonces']['delete'])){
    			$html .= ' &bull; <a href="#" class="text-danger ifcf7-delete-attachment" data-ifcf7-nonce="' . $metadata['nonces']['delete'] . '">' . __('Delete') . '</a>';
    		}
    		$html .= '</div>';
    		$html .= '</div>';
    		$html .= '</div>';
    	}
    	$html .= '</div>';
        return $html . $html_orig;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function get_inserted_post_id(){
		return self::$inserted_post_id;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function get_inserted_user_id(){
		return self::$inserted_user_id;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function get_updated_post_id(){
		return self::$updated_post_id;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function get_updated_user_id(){
		return self::$updated_user_id;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function init(){
        register_post_type('ifcf7-submission', [
            'labels' => Utilities::post_type_labels('Submission', 'Submissions', false),
            'show_in_admin_bar' => false,
            'show_in_menu' => 'wpcf7',
            'show_ui' => true,
            'supports' => ['custom-fields', 'title'],
        ]);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
        add_action('rest_api_init', [__CLASS__, 'rest_api_init']);
        add_action('wpcf7_enqueue_scripts', [__CLASS__, 'wpcf7_enqueue_scripts']);
        add_filter('ifcf7_form_tag_html', [__CLASS__, 'form_tag_html'], 30, 5);
		add_filter('init', [__CLASS__, 'init']);
		add_filter('wpcf7_before_send_mail', [__CLASS__, 'wpcf7_before_send_mail'], 20, 3);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function store_submission($action = '', $meta_type = '', $object_id = 0, $contact_form = null, $submission = null){
		if(!in_array($action, ['insert', 'update'])){
			return;
		}
		if(!in_array($meta_type, ['post', 'user'])){
			return;
		}
		if('post' === $meta_type){
            $post = get_post($object_id);
            if(empty($post)){
                return;
            }
		} elseif('user' === $meta_type){
            $user = get_userdata($object_id);
            if(empty($user)){
                return;
            }
		}
		if(null === $contact_form){
			$contact_form = wpcf7_get_current_contact_form();
		}
		if(null === $contact_form){
			return;
		}
		if(null === $submission){
			$submission = \WPCF7_Submission::get_instance();
		}
		if(null === $submission){
			return;
		}
        if('insert' === $action){
            if('post' === $meta_type){
                self::$inserted_post_id = $object_id;
            } elseif('user' === $meta_type){
                self::$inserted_user_id = $object_id;
            }
        } elseif('update' === $action){
            if('post' === $meta_type){
                self::$updated_post_id = $object_id;
            } elseif('user' === $meta_type){
                self::$updated_user_id = $object_id;
            }
        }
        if('post' === $meta_type){
            $the_post = wp_is_post_revision($object_id);
            if($the_post){
                $object_id = $the_post; // Make sure meta is added to the post, not a revision.
            }
        }
        if('insert' === $action){
            foreach(Helper::get_meta_data() as $key => $value){
                add_metadata($meta_type, $object_id, $key, $value, true);
            }
        }
        foreach($submission->get_posted_data() as $key => $value){
            if(is_array($value)){
                delete_metadata($meta_type, $object_id, $key);
                foreach($value as $single){
                    add_metadata($meta_type, $object_id, $key, $single);
                }
            } else {
                update_metadata($meta_type, $object_id, $key, $value);
            }
        }
        if('post' === $meta_type){
            $post_id = $object_id;
        } else {
            $post_id = 0;
        }
        foreach($submission->uploaded_files() as $key => $value){
            foreach((array) $value as $single){
                $attachment_id = Files::upload_file($single, $post_id);
                if(!is_wp_error($attachment_id)){
                    add_metadata($meta_type, $object_id, $key . '_id', $attachment_id);
                }
            }
            delete_metadata($meta_type, $object_id, $key);
        }
        if('post' === $meta_type){
            do_action('ifcf7_' . $action . '_post_' . $post->post_type, $object_id, $contact_form, $submission);
        } else {
            foreach($user->roles as $role){
                do_action('ifcf7_' . $action . '_user_' . $role, $object_id, $contact_form, $submission);
            }
        }
		do_action('ifcf7_' . $action . '_' . $meta_type, $object_id, $contact_form, $submission);
		do_action('ifcf7_store_submission', $action, $meta_type, $object_id, $contact_form, $submission);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function rest_api_init(){
        register_rest_route('ifcf7/v1', '/uploaded-files/(?P<meta_type>post|user)/(?P<object_id>\d+)/(?P<meta_key>[\w-]+)/(?P<meta_value>\d+)', [
    		'callback' => function($request){
    			$meta_type = $request->get_param('meta_type');
    			$object_id = $request->get_param('object_id');
    			$meta_key = $request->get_param('meta_key');
    			$meta_value = $request->get_param('meta_value');
                $post = wp_delete_attachment($meta_value);
                if(empty($post)){
                    return [
        				'ifcf7_status' => 0,
        			];
                }
    			return [
    				'ifcf7_status' => (int) delete_metadata($meta_type, $object_id, $meta_key . '_id', $meta_value),
    			];
    		},
    		'methods' => 'DELETE',
    		'permission_callback' => function($request){
    			$object_id = $request->get_param('object_id');
    			$meta_value = $request->get_param('meta_value');
    			$nonce = $request->get_param('ifcf7_nonce');
    			return (current_user_can('edit_post', $object_id) and wp_verify_nonce($nonce, 'delete-post_' . $meta_value));
    		},
    	]);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_before_send_mail($contact_form, &$abort, $submission){
        if($contact_form->is_true('do_not_store')){
            return;
        }
        if(!$submission->is('init')){
            return; // prevent conflicts with actions and other plugins
        }
        $uniqid = uniqid();
        $post_id = wp_insert_post([
            'post_name' => 'ifcf7-submission-' . $uniqid,
			'post_status' => self::get_post_status($contact_form),
			'post_title' => '[ifcf7-submission ' . $uniqid . ']',
			'post_type' => self::get_post_type($contact_form),
		], true);
        if(is_wp_error($post_id)){
            $abort = true; // prevent mail_sent and mail_failed actions
            $submission->set_response($post_id->get_error_message());
            $submission->set_status('aborted'); // try to prevent conflicts with actions and other plugins
            return;
        }
        self::store_submission('insert', 'post', $post_id, $contact_form, $submission);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_enqueue_scripts(){
        wp_enqueue_script('wp-api');
		Loader::enqueue_asset('uploaded-files', 'uploaded-files.js', ['wp-api']);
		Loader::add_inline_script('uploaded-files', 'ifcf7_uploaded_files.load();');
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_mail_failed($contact_form){
        $submission = \WPCF7_Submission::get_instance();
        if(null === $submission){
            $response = $contact_form->message('mail_sent_ng');
            $status = 'mail_failed';
        } else {
            $response = $submission->get_response();
            $status = $submission->get_status();
        }
		self::update_response($response, $status);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_mail_sent($contact_form){
        $submission = \WPCF7_Submission::get_instance();
        if(null === $submission){
            $response = $contact_form->message('mail_sent_ok');
            $status = 'mail_sent';
        } else {
            $response = $submission->get_response();
            $status = $submission->get_status();
        }
		self::update_response($response, $status);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

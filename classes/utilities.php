<?php namespace IFCF7;

final class Utilities {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static function loading_message($response = []){
        if('mail_sent' !== $response['status']){
            return '';
        }
        $contact_form = wpcf7_get_current_contact_form();
        if(null === $contact_form){
            return '';
        }
        if($contact_form->is_true('loading_message')){
            return __('Loading&hellip;');
		}
        $message = $contact_form->pref('loading_message');
        if(null === $message){
            return '';
        }
        return $message;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static function redirect_url($response = []){
        if('mail_sent' !== $response['status']){
            return '';
        }
        $url = self::redirect_raw_url();
		if(!wpcf7_is_url($url)){
			return '';
		}
		$inserted_post_id = Storage::get_inserted_post_id();
		$inserted_user_id = Storage::get_inserted_user_id();
		$updated_post_id = Storage::get_updated_post_id();
		$updated_user_id = Storage::get_updated_user_id();
        $nonce = '';
        if(!empty($inserted_post_id)){
            $url = add_query_arg('inserted_post_id', $inserted_post_id, $url);
            $nonce = wp_create_nonce('ifcf7-inserted-post_' . $inserted_post_id);
        } elseif(!empty($inserted_user_id)){
            $url = add_query_arg('inserted_user_id', $inserted_user_id, $url);
            $nonce = wp_create_nonce('ifcf7-inserted-user_' . $inserted_post_id);
        } elseif(!empty($updated_post_id)){
            $url = add_query_arg('updated_post_id', $updated_post_id, $url);
            $nonce = wp_create_nonce('ifcf7-updated-post_' . $inserted_post_id);
        } elseif(!empty($updated_user_id)){
            $url = add_query_arg('updated_user_id', $updated_user_id, $url);
            $nonce = wp_create_nonce('ifcf7-updated-user_' . $inserted_post_id);
        }
        if(!empty($nonce)){
            $url = add_query_arg('ifcf7_nonce', $nonce, $url);
        }
		return $url;
	}

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static function redirect_raw_url(){
        $contact_form = wpcf7_get_current_contact_form();
        if(null === $contact_form){
            return '';
        }
		if($contact_form->is_true('redirect_url')){
            $submission = \WPCF7_Submission::get_instance();
            if(null === $submission){
                return home_url();
            }
			$redirect_to = $submission->get_posted_data('ifcf7_redirect_to');
			if(!empty($redirect_to) and wpcf7_is_url($redirect_to)){
				return $redirect_to;
			}
			return $submission->get_meta('url');
		}
        $url = $contact_form->pref('redirect_url');
        if(null === $url){
            return '';
        }
		$submission = \WPCF7_Submission::get_instance();
		if(null === $submission){
			return $url;
		}
		$redirect_to = $submission->get_posted_data('ifcf7_redirect_to');
		if(!empty($redirect_to) and wpcf7_is_url($redirect_to)){
			return $redirect_to;
		}
        return $url;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static function thank_you_message($response = []){
        if('mail_sent' !== $response['status']){
            return '';
        }
        $contact_form = wpcf7_get_current_contact_form();
        if(null === $contact_form){
            return '';
        }
        if($contact_form->is_true('thank_you_message')){
            return $response['message'];
		}
        $message = $contact_form->pref('thank_you_message');
        if(null === $message){
            return '';
        }
        $message = wpcf7_mail_replace_tags($message);
		$message = apply_filters('wpcf7_display_message', $message, $response['status']);
		$message = wp_strip_all_tags($message);
        return $message;
	}

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function get_inserted_post_id(){
        if(empty($_GET['inserted_post_id'])){
            return 0;
        }
        if(empty($_GET['ifcf7_nonce']) or !wp_verify_nonce($_GET['ifcf7_nonce'], 'ifcf7-inserted-post_' . $_GET['inserted_post_id'])){
			return new \WP_Error('ifcf7_error', __('The link you followed has expired.'));
		}
		return (int) $_GET['inserted_post_id'];
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function get_inserted_user_id(){
        if(empty($_GET['inserted_user_id'])){
            return 0;
        }
        if(empty($_GET['ifcf7_nonce']) or !wp_verify_nonce($_GET['ifcf7_nonce'], 'ifcf7-inserted-user_' . $_GET['inserted_user_id'])){
			return new \WP_Error('ifcf7_error', __('The link you followed has expired.'));
		}
		return (int) $_GET['inserted_user_id'];
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function get_updated_post_id(){
        if(empty($_GET['updated_post_id'])){
            return 0;
        }
        if(empty($_GET['ifcf7_nonce']) or !wp_verify_nonce($_GET['ifcf7_nonce'], 'ifcf7-updated-post_' . $_GET['updated_post_id'])){
			return new \WP_Error('ifcf7_error', __('The link you followed has expired.'));
		}
		return (int) $_GET['updated_post_id'];
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function get_updated_user_id(){
        if(empty($_GET['updated_user_id'])){
            return 0;
        }
        if(empty($_GET['ifcf7_nonce']) or !wp_verify_nonce($_GET['ifcf7_nonce'], 'ifcf7-updated-user_' . $_GET['updated_user_id'])){
			return new \WP_Error('ifcf7_error', __('The link you followed has expired.'));
		}
		return (int) $_GET['updated_user_id'];
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_action('wpcf7_enqueue_scripts', [__CLASS__, 'wpcf7_enqueue_scripts']);
		add_action('wpcf7_enqueue_styles', [__CLASS__, 'wpcf7_enqueue_styles']);
        add_filter('pre_delete_post', [__CLASS__, 'pre_delete_post'], 10, 3);
		add_filter('wpcf7_autop_or_not', [__CLASS__, 'wpcf7_autop_or_not']);
		add_filter('wpcf7_feedback_response', [__CLASS__, 'wpcf7_feedback_response'], 10, 2);
		add_filter('wpcf7_form_elements', 'do_shortcode');
		add_filter('wpcf7_form_hidden_fields', [__CLASS__, 'wpcf7_form_hidden_fields']);
		add_filter('wpcf7_remote_ip_addr', [__CLASS__, 'wpcf7_remote_ip_addr']);
        add_filter('wpcf7_validate_password', [__CLASS__, 'wpcf7_password_validation_filter'], 10, 2);
        add_filter('wpcf7_validate_password*', [__CLASS__, 'wpcf7_password_validation_filter'], 10, 2);
        add_filter('wpcf7_validate_radio*', 'wpcf7_checkbox_validation_filter', 10, 2);
        add_filter('wpcf7_verify_nonce', 'is_user_logged_in');
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function pre_delete_post($delete, $post, $force_delete){
        if('wpcf7_contact_form' !== $post->post_type){
            return $delete;
        }
       return false;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function post_type_labels($singular = '', $plural = '', $all = true){
		if(!$singular or !$plural){
			return [];
		}
		return [
			'name' => $plural,
			'singular_name' => $singular,
			'add_new' => 'Add New',
			'add_new_item' => 'Add New ' . $singular,
			'edit_item' => 'Edit ' . $singular,
			'new_item' => 'New ' . $singular,
			'view_item' => 'View ' . $singular,
			'view_items' => 'View ' . $plural,
			'search_items' => 'Search ' . $plural,
			'not_found' => 'No ' . strtolower($plural) . ' found.',
			'not_found_in_trash' => 'No ' . strtolower($plural) . ' found in Trash.',
			'parent_item_colon' => 'Parent ' . $singular . ':',
			'all_items' => ($all ? 'All ' : '') . $plural,
			'archives' => $singular . ' Archives',
			'attributes' => $singular . ' Attributes',
			'insert_into_item' => 'Insert into ' . strtolower($singular),
			'uploaded_to_this_item' => 'Uploaded to this ' . strtolower($singular),
			'featured_image' => 'Featured image',
			'set_featured_image' => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image' => 'Use as featured image',
			'filter_items_list' => 'Filter ' . strtolower($plural) . ' list',
			'items_list_navigation' => $plural . ' list navigation',
			'items_list' => $plural . ' list',
			'item_published' => $singular . ' published.',
			'item_published_privately' => $singular . ' published privately.',
			'item_reverted_to_draft' => $singular . ' reverted to draft.',
			'item_scheduled' => $singular . ' scheduled.',
			'item_updated' => $singular . ' updated.',
		];
	}

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_autop_or_not($autop){
        $contact_form = wpcf7_get_current_contact_form();
        if(null === $contact_form){
            return $autop;
        }
        return $contact_form->is_true('autop');
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_enqueue_scripts(){
		Loader::enqueue_asset('utilities', 'utilities.js', ['contact-form-7']);
		Loader::add_inline_script('utilities', 'ifcf7_utilities.load();');
		if(!empty($_SERVER['HTTP_USER_AGENT']) and false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile')){ // Many mobile devices (all iPhone, iPad, etc.)
            Loader::add_inline_script('utilities', 'jQuery(function(){ ifcf7_utilities.fix_mobile_numbers(); });');
    	}
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_enqueue_styles(){
		Loader::enqueue_asset('utilities', 'utilities.css', ['contact-form-7']);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_feedback_response($response, $result){
        $response['loading_message'] = self::loading_message($response);
        $response['redirect_url'] = self::redirect_url($response);
        $response['thank_you_message'] = self::thank_you_message($response);
        return $response;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_form_hidden_fields($hidden_fields){
		if(!empty($_GET['redirect_to']) and wpcf7_is_url($_GET['redirect_to'])){
			$hidden_fields['ifcf7_redirect_to'] = $_GET['redirect_to'];
		}
		return $hidden_fields;
	}

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_password_validation_filter($result, $tag){
		$value = isset($_POST[$tag->name]) ? trim(wp_unslash(strtr((string) $_POST[$tag->name], "\n", ' '))) : '';
		if('password' === $tag->basetype and $tag->is_required() and '' === $value){
			$result->invalidate($tag, wpcf7_get_message('invalid_required'));
		}
		return wpcf7_text_validation_filter($result, $tag);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_remote_ip_addr($ip_addr){
        if(!empty($_SERVER['HTTP_CF_CONNECTING_IP'])){
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if(class_exists('wfUtils')){
            return \wfUtils::getIP();
        }
        return $ip_addr;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

<?php namespace IFCF7;

final class Edit_User {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static $current_user_id = 0;

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function get_user_id($contact_form = null, $submission = null){
		if(null === $contact_form){
			$contact_form = wpcf7_get_current_contact_form();
		}
		if(null === $contact_form){
			return new \WP_Error('ifcf7_error', __('The requested contact form was not found.', 'contact-form-7'));
		}
		if(!self::is_action($contact_form)){
			return new \WP_Error('ifcf7_error', sprintf(__('%1$s is not of type %2$s.'), $type, 'edit_user'));
		}
		if(null === $submission){
			$submission = \WPCF7_Submission::get_instance();
		}
		$missing = [];
		if(null === $submission){
			$nonce = null;
			$user_id = $contact_form->shortcode_attr('user_id');
			if(empty($user_id)){
				$missing[] = 'user_id';
			}
		} else {
			$nonce = $submission->get_posted_data('ifcf7_nonce');
			if(empty($nonce)){
				$missing[] = 'ifcf7_nonce';
			}
			$user_id = $submission->get_posted_data('ifcf7_user_id');
			if(empty($user_id)){
				$missing[] = 'ifcf7_user_id';
			}
		}
		if($missing){
			return new \WP_Error('ifcf7_error', sprintf(__('Missing parameter(s): %s'), implode(', ', $missing)) . '.');
		}
		if(null !== $nonce and !wp_verify_nonce($nonce, 'ifcf7-edit-user_' . $user_id)){
			return new \WP_Error('ifcf7_error', __('The link you followed has expired.'));
		}
		$user_id = self::sanitize_user_id($user_id);
		if(0 === $user_id){
			return new \WP_Error('ifcf7_error', __('Invalid post ID.'));
		}
		if(!current_user_can('edit_user', $user_id)){
			$message = __('Sorry, you are not allowed to edit this user.') . ' ' . __('You need a higher level of permission.');
			return new \WP_Error('ifcf7_error', $message);
		}
		return $user_id;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static function is_action($contact_form = null){
		if(null === $contact_form){
			$contact_form = wpcf7_get_current_contact_form();
		}
		if(null === $contact_form){
			return false;
		}
		$action = $contact_form->pref('action');
		if(empty($action)){
			return false;
		}
		$action = \WP_REST_Request::canonicalize_header_name($action);
		return ('edit_user' === $action);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	/*private static function output($output, $user_id, $attr, $content, $tag){
		if(!function_exists('str_get_html')){
			require_once(plugin_dir_path(Loader::get_file()) . 'includes/simple-html-dom-1.9.1.php');
        }
		$html = str_get_html($output);
		$wp_nonce = $html->find('[name="_wpnonce"]', 0);
		$original_wp_nonce = $wp_nonce->value;
		$ifcf7_nonce = $html->find('[name="ifcf7_nonce"]', 0);
		$original_ifcf7_nonce = $ifcf7_nonce->value;
		$ifcf7_nonce_html = $ifcf7_nonce->outertext;
		$ifcf7_user_id = $html->find('[name="ifcf7_user_id"]', 0);
		$ifcf7_user_id_html = $ifcf7_user_id->outertext;
		$current_user_id = get_current_user_id();
		wp_set_current_user($user_id);
		$output = wpcf7_contact_form_tag_func($attr, $content, $tag);
		wp_set_current_user($current_user_id);
		$html = str_get_html($output);
		$wp_nonce = $html->find('[name="_wpnonce"]', 0);
		$wp_nonce->value = $original_wp_nonce;
		$ifcf7_nonce = $html->find('[name="ifcf7_nonce"]', 0);
		if(empty($ifcf7_nonce)){
			$wp_nonce->outertext .= $ifcf7_nonce_html;
		} else {
			$ifcf7_nonce->value = $original_ifcf7_nonce;
		}
		$ifcf7_user_id = $html->find('[name="ifcf7_user_id"]', 0);
		if(empty($ifcf7_user_id)){
			$wp_nonce->outertext .= $ifcf7_user_id_html;
		}
		$output = $html->save();
		return $output;
	}*/

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function sanitize_user_id($user_id){
		$user = false;
		if(is_numeric($user_id)){
			$user = get_userdata($user_id);
		} else {
			if('current' === $user_id){
				if(is_user_logged_in()){
					$user = wp_get_current_user();
				}
			}
		}
		if(!$user){
			return 0;
		}
		return $user->ID;
	}

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function ifcf7_free_text_value($value, $name){
		if('' !== $value){
			return $value;
		}
		if(!self::is_action()){
			return $value;
		}
		$user_id = self::get_user_id();
		if(is_wp_error($user_id)){
			return $value;
		}
		return get_user_meta($user_id, $name . '_free_text', true);
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function do_shortcode_tag($output, $tag, $attr, $m){
		if('contact-form-7' !== $tag){
			return $output;
		}
		$contact_form = wpcf7_get_current_contact_form();
		if(null === $contact_form){
			return $output;
		}
		if(!self::is_action($contact_form)){
			return $output;
		}
		$user_id = self::get_user_id();
		if(is_wp_error($user_id)){
			return '<div class="alert alert-danger" role="alert">' . $user_id->get_error_message() . '</div>';
		}
		//$content = isset($m[5]) ? $m[5] : null;
		//$output = self::output($output, $user_id, $attr, $content, $tag);
		return $output;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_action('wpcf7_before_send_mail', [__CLASS__, 'wpcf7_before_send_mail'], 10, 3);
		add_filter('do_shortcode_tag', [__CLASS__, 'do_shortcode_tag'], 10, 4);
		add_filter('ifcf7_free_text_value', [__CLASS__, 'ifcf7_free_text_value'], 10, 2);
		add_filter('shortcode_atts_wpcf7', [__CLASS__, 'shortcode_atts_wpcf7'], 10, 3);
		add_filter('wpcf7_form_hidden_fields', [__CLASS__, 'wpcf7_form_hidden_fields']);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function shortcode_atts_wpcf7($out, $pairs, $atts){
		if(isset($atts['user_id'])){
			$out['user_id'] = $atts['user_id'];
		}
		return $out;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_before_send_mail($contact_form, &$abort, $submission){
		if(!self::is_action($contact_form)){
			return;
		}
		if($abort or !$submission->is('init')){
			return; // prevent conflicts with other plugins
		}
		$abort = true; // prevent mail_sent and mail_failed actions
		$user_id = self::get_user_id($contact_form, $submission);
		if(is_wp_error($user_id)){
			$submission->set_response($user_id->get_error_message());
			$submission->set_status('aborted'); // try to prevent conflicts with other plugins
			return;
		}
		$response = __('User updated.');
		if(Helper::skip_mail($contact_form)){
			$submission->set_response($response);
			$submission->set_status('mail_sent');
		} else {
			if(Helper::send_mail($contact_form)){
				$submission->set_response($response . ' ' . $contact_form->message('mail_sent_ok'));
				$submission->set_status('mail_sent');
			} else {
				$submission->set_response($response . ' ' . $contact_form->message('mail_sent_ng'));
				$submission->set_status('mail_failed');
			}
		}
		Storage::store_submission('update', 'user', $user_id, $contact_form, $submission);
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_form_elements($elements){
		if(!self::is_action()){
			return $elements;
		}
		$user_id = self::get_user_id();
		if(is_wp_error($user_id)){
			return $elements;
		}
		wp_set_current_user(self::$current_user_id);
		self::$current_user_id = 0;
		return $elements;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_form_hidden_fields($hidden_fields){
		if(!self::is_action()){
			return $hidden_fields;
		}
		$user_id = self::get_user_id();
		if(is_wp_error($user_id)){
			return $hidden_fields;
		}
		$hidden_fields['ifcf7_nonce'] = wp_create_nonce('ifcf7-edit-user_' . $user_id);
		$hidden_fields['ifcf7_user_id'] = $user_id;
		self::$current_user_id = get_current_user_id();
		wp_set_current_user($user_id);
		return $hidden_fields;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

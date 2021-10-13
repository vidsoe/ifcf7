<?php namespace IFCF7;

final class Retrieve_Password {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static $post_id = 0, $posted_data = [];

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function get_posted_data($name = ''){
		if('' === $name){
			return self::$posted_data;
		}
		if(!isset(self::$posted_data[$name])){
			return '';
		}
		return self::$posted_data[$name];
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
		return ('retrieve_password' === $action);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function custom_form(){
        if(empty(self::$post_id)){
            return;
        }
        $url = get_permalink(self::$post_id);
    	if(!empty($_GET)){
    		$url = add_query_arg($_GET, $url);
    	}
    	wp_safe_redirect($url);
    	exit;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function custom_page($post_id = 0){
        $post = get_post($post_id);
        if(!$post or 'page' !== $post->post_type or 'publish' !== $post->post_status){
            return;
        }
        self::$post_id = $post->ID;
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
		$missing = [];
		$service = \WPCF7_RECAPTCHA::get_instance();
		if(!$service->is_active()){
			$missing[] = 'reCAPTCHA';
		}
		$tags = wp_list_pluck($contact_form->scan_form_tags(), 'type', 'name');
		if(!isset($tags['user_email']) and !isset($tags['user_login'])){
			$missing[] = 'user_login';
		}
		if($missing){
			$error = current_user_can('manage_options') ? sprintf(__('Missing parameter(s): %s'), implode(', ', $missing)) . '.' : __('Something went wrong.');
			return '<div class="alert alert-danger" role="alert">' . $error . '</div>';
		}
		if(isset($tags['user_email']) and isset($tags['user_login'])){
			$error = current_user_can('manage_options') ? sprintf(__('Invalid parameter(s): %s'), __('Duplicated username or email address.')) : __('Something went wrong.');
			return '<div class="alert alert-danger" role="alert">' . $error . '</div>';
		}
		$invalid = [];
		if(isset($tags['user_email']) and $tags['user_email'] !== 'email*'){
			$invalid[] = 'user_email';
		}
		if(isset($tags['user_login']) and $tags['user_login'] !== 'text*'){
			$invalid[] = 'user_login';
		}
		if($invalid){
			$error = current_user_can('manage_options') ? sprintf(__('Invalid parameter(s): %s'), implode(', ', $invalid)) . '.' : __('Something went wrong.');
			return '<div class="alert alert-danger" role="alert">' . $error . '</div>';
		}
		if(is_user_logged_in()){
			$error = __('You are logged in already. No need to register again!');
			//$error = bc_first_p($error);
			return '<div class="alert alert-warning" role="alert">' . $error . '</div>';
		}
		$error = '';
		if(isset($_GET['error'])){
			if('invalidkey' === $_GET['error']){
				$error = __('<strong>Error</strong>: Your password reset link appears to be invalid. Please request a new link below.');
			} elseif ( 'expiredkey' === $_GET['error'] ) {
				$error = __('<strong>Error</strong>: Your password reset link has expired. Please request a new link below.');
			}
		}
		return $error . $output;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_action('login_form_lostpassword', [__CLASS__, 'custom_form']);
        add_action('login_form_retrievepassword', [__CLASS__, 'custom_form']);
		add_action('wpcf7_before_send_mail', [__CLASS__, 'wpcf7_before_send_mail'], 10, 3);
		add_filter('do_shortcode_tag', [__CLASS__, 'do_shortcode_tag'], 10, 4);
		add_filter('wpcf7_posted_data', [__CLASS__, 'wpcf7_posted_data']);
		add_filter('wpcf7_validate_email*', [__CLASS__, 'wpcf7_validate_email'], 20, 2);
		add_filter('wpcf7_validate_text*', [__CLASS__, 'wpcf7_validate_text'], 20, 2);
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
		$user_email = self::get_posted_data('user_email');
		$user_login = self::get_posted_data('user_login');
		if('' === $user_login){
			$user_login = $user_email;
		}
		$result = retrieve_password($user_login);
		if(is_wp_error($result)){
			$message = $result->get_error_message();
			$submission->set_response(wp_strip_all_tags($message));
			$submission->set_status('aborted'); // try to prevent conflicts with other plugins
			return;
		}
		$message = sprintf(__('Check your email for the confirmation link, then visit the <a href="%s">login page</a>.'), wp_login_url());
		if(Helper::skip_mail($contact_form)){
			$submission->set_response(wp_strip_all_tags($message));
			$submission->set_status('mail_sent');
		} else {
			if(Helper::send_mail($contact_form)){
				$message .= ' ' . $contact_form->message('mail_sent_ok');
				$submission->set_response(wp_strip_all_tags($message));
				$submission->set_status('mail_sent');
			} else {
				$message .= ' ' . $contact_form->message('mail_sent_ng');
				$submission->set_response(wp_strip_all_tags($message));
				$submission->set_status('mail_failed');
			}
		}
		$user = get_user_by('user_login', trim(wp_unslash($user_login)));
		do_action('ifcf7_retrieve_password', $user->ID, $contact_form, $submission);
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_posted_data($posted_data){
		if(!self::is_action()){
			return $posted_data;
		}
		$fields = ['user_email', 'user_login'];
		foreach($fields as $field){
			if(isset($posted_data[$field])){
				self::$posted_data[$field] = $posted_data[$field];
				unset($posted_data[$field]);
			}
		}
		return $posted_data;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_validate_email($result, $tag){
		if('user_email' !== $tag->name){
			return $result;
		}
		if(!self::is_action()){
			return $result;
		}
		$user_email = self::get_posted_data('user_email');
		if(!email_exists($user_email)){
			$message = __('Unknown email address. Check again or try your username.');
			//$message = bc_first_p($message);
			$result->invalidate($tag, wp_strip_all_tags($message));
			return $result;
		}
		return $result;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_validate_text($result, $tag){
		if($tag->name !== 'user_login'){
			return $result;
		}
		if(!self::is_action()){
			return $result;
		}
		$user_login = self::get_posted_data('user_login');
		if(wpcf7_is_email($user_login)){
			$message = __('Unknown email address. Check again or try your username.');
			$user = get_user_by('email', $user_login);
		} else {
			$message = __('Unknown username. Check again or try your email address.');
			$user = get_user_by('login', $user_login);
		}
		if(!$user){
			$result->invalidate($tag, wp_strip_all_tags($message));
			return $result;
		}
		return $result;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

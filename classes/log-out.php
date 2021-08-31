<?php namespace IFCF7;

final class Log_Out {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static $posted_data = [];

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
		return ('log_out' === $action);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
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
		if(!is_user_logged_in()){
			$error = __('You are not currently logged in.');
			return '<div class="alert alert-warning" role="alert">' . $error . '</div>';
		}
		return $output;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_action('wpcf7_before_send_mail', [__CLASS__, 'wpcf7_before_send_mail'], 10, 3);
		add_filter('do_shortcode_tag', [__CLASS__, 'do_shortcode_tag'], 10, 4);
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
		$user_id = get_current_user_id();
		wp_logout();
		$message = __('You are now logged out.');
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
		do_action('ifcf7_log_out', $user_id, $contact_form, $submission);
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

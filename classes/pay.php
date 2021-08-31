<?php namespace IFCF7;

final class Pay {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static $posted_data = [];

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
		return ('payment_intent' === $action);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function add_data_options(){
		global $wp_locale;
		$cc_exp_mm = $wp_locale->month;
		$cc_exp_mm = array_map('ucfirst', $cc_exp_mm);
		foreach($cc_exp_mm as $key => $value){
			$cc_exp_mm[$key] = $key . ' - ' . $value;
		}
		Data_Options::add_data_option('cc_exp_mm', $cc_exp_mm);
		$cc_exp_yy = current_time('Y');
		$cc_exp_yy = range($cc_exp_yy, ($cc_exp_yy + 15));
		Data_Options::add_data_option('cc_exp_yy', $cc_exp_yy);
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
		if(!has_filter('ifcf7_payment_intent')){
			$error = current_user_can('manage_options') ? sprintf(__("Method '%s' not implemented. Must be overridden in subclass."), 'ifcf7_payment_intent') : __('Something went wrong.');
			return '<div class="alert alert-danger" role="alert">' . $error . '</div>';
		}
		$missing = [];
		$service = \WPCF7_RECAPTCHA::get_instance();
		if(!$service->is_active()){
			$missing[] = 'reCAPTCHA';
		}
		$tags = wp_list_pluck($contact_form->scan_form_tags(), 'type', 'name');
		$fields = ['cc-amount', 'cc-csc', 'cc-exp-mm', 'cc-exp-yy', 'cc-number', 'cc-name'];
		foreach($fields as $field){
			if(!isset($tags[$field])){
				$missing[] = $field;
			}
		}
		if($missing){
			$error = current_user_can('manage_options') ? sprintf(__('Missing parameter(s): %s'), implode(', ', $missing)) . '.' : __('Something went wrong.');
			return '<div class="alert alert-danger" role="alert">' . $error . '</div>';
		}
		$invalid = [];
		if(!in_array($tags['cc-amount'], ['hidden', 'number*', 'select*'])){
			$invalid[] = 'cc-amount';
		}
		if($tags['cc-csc'] !== 'number*'){
			$invalid[] = 'cc-csc';
		}
		if($tags['cc-exp-mm'] !== 'select*'){
			$invalid[] = 'cc-exp-mm';
		}
		if($tags['cc-exp-yy'] !== 'select*'){
			$invalid[] = 'cc-exp-yy';
		}
		if($tags['cc-name'] !== 'text*'){
			$invalid[] = 'cc-name';
		}
		if($tags['cc-number'] !== 'number*'){
			$invalid[] = 'cc-number';
		}
		if($invalid){
			$error = current_user_can('manage_options') ? sprintf(__('Invalid parameter(s): %s'), implode(', ', $invalid)) . '.' : __('Something went wrong.');
			return '<div class="alert alert-danger" role="alert">' . $error . '</div>';
		}
		return $output;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_action('after_setup_theme', [__CLASS__, 'add_data_options']);
		add_action('wpcf7_before_send_mail', [__CLASS__, 'wpcf7_before_send_mail'], 10, 3);
		add_filter('do_shortcode_tag', [__CLASS__, 'do_shortcode_tag'], 10, 4);
		add_filter('wpcf7_posted_data', [__CLASS__, 'wpcf7_posted_data']);
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
		$post_id = wp_insert_post([
			'post_status' => 'private',
			'post_title' => '[ifcf7-payment-intent]',
			'post_type' => 'ifcf7-payment-intent',
		], true);
		if(is_wp_error($post_id)){
			$submission->set_response($post_id->get_error_message());
			$submission->set_status('aborted'); // try to prevent conflicts with other plugins
			return;
		}
		$payment_intent = new Payment_Intent($post_id);
		$payment_intent = apply_filters('ifcf7_payment_intent', $payment_intent, self::$posted_data, $contact_form, $submission);
		if($payment_intent instanceof Payment_Intent){
			$data = $payment_intent->get_data();
			$message = $payment_intent->get_message();
			$status = $payment_intent->get_status();
		} else {
			$data = $payment_intent;
			$message = __('Invalid object type.');
			$status = false;
			update_post_meta($post_id, 'ifcf7_payment_intent_data', $data);
			update_post_meta($post_id, 'ifcf7_payment_intent_message', $message);
			update_post_meta($post_id, 'ifcf7_payment_intent_status', $status);
		}
		if(false === $status){
			$submission->set_response($message);
			$submission->set_status('aborted'); // try to prevent conflicts with other plugins
		} else {
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
		}
		Storage::store_submission('insert', 'post', $post_id, $contact_form, $submission);
		do_action('ifcf7_pay', $payment_intent, $contact_form, $submission);
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_posted_data($posted_data){
		if(!self::is_action()){
			return $posted_data;
		}
		$fields = ['cc-amount', 'cc-csc', 'cc-exp-mm', 'cc-exp-yy', 'cc-number', 'cc-name'];
		foreach($fields as $field){
			if(isset($posted_data[$field])){
				self::$posted_data[$field] = $posted_data[$field];
				unset($posted_data[$field]);
			}
		}
		return $posted_data;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

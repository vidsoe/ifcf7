<?php namespace IFCF7;

final class Edit_Post {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function get_post_id($contact_form = null, $submission = null){
		if(null === $contact_form){
			$contact_form = wpcf7_get_current_contact_form();
		}
		if(null === $contact_form){
			return new \WP_Error('ifcf7_error', __('The requested contact form was not found.', 'contact-form-7'));
		}
		if(!self::is_action($contact_form)){
			return new \WP_Error('ifcf7_error', sprintf(__('%1$s is not of type %2$s.'), $type, 'edit_post'));
		}
		if(null === $submission){
			$submission = \WPCF7_Submission::get_instance();
		}
		$missing = [];
		if(null === $submission){
			$nonce = null;
			$post_id = $contact_form->shortcode_attr('post_id');
			if(empty($post_id)){
				$missing[] = 'post_id';
			}
		} else {
			$nonce = $submission->get_posted_data('ifcf7_nonce');
			if(empty($nonce)){
				$missing[] = 'ifcf7_nonce';
			}
			$post_id = $submission->get_posted_data('ifcf7_post_id');
			if(empty($post_id)){
				$missing[] = 'ifcf7_post_id';
			}
		}
		if($missing){
			return new \WP_Error('ifcf7_error', sprintf(__('Missing parameter(s): %s'), implode(', ', $missing)) . '.');
		}
		if(null !== $nonce and !wp_verify_nonce($nonce, 'ifcf7-edit-post_' . $post_id)){
			return new \WP_Error('ifcf7_error', __('The link you followed has expired.'));
		}
		$post_id = self::sanitize_post_id($post_id);
		if(0 === $post_id){
			return new \WP_Error('ifcf7_error', __('Invalid post ID.'));
		}
		if(!current_user_can('edit_post', $post_id)){
			if('post' === get_post_type($post_id)){
				$message = __('Sorry, you are not allowed to edit this post.');
			} else {
				$message = __('Sorry, you are not allowed to edit this item.');
			}
			$message .=  ' ' . __('You need a higher level of permission.');
			return new \WP_Error('ifcf7_error', $message);
		}
		if('trash' === get_post_status($post_id)){
			return new \WP_Error('ifcf7_error', __('You can&#8217;t edit this item because it is in the Trash. Please restore it and try again.'));
		}
		return $post_id;
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
		return ('edit_post' === $action);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	/*private static function output($post_id, $attr, $content, $tag){
		global $post;
		$post = get_post($post_id);
		setup_postdata($post);
		$output = wpcf7_contact_form_tag_func($attr, $content, $tag);
		wp_reset_postdata();
		return $output;
	}*/

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function sanitize_post_id($post_id){
		$post = null;
		if(is_numeric($post_id)){
			$post = get_post($post_id);
		} else {
			if('current' === $post_id){
				if(in_the_loop()){
					$post = get_post();
				}
			}
		}
		if(null === $post){
			return 0;
		}
		return $post->ID;
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
		$post_id = self::get_post_id();
		if(is_wp_error($post_id)){
			return $value;
		}
		return get_post_meta($post_id, $name . '_free_text', true);
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
		$post_id = self::get_post_id();
		if(is_wp_error($post_id)){
			return '<div class="alert alert-danger" role="alert">' . $post_id->get_error_message() . '</div>';
		}
		//$content = isset($m[5]) ? $m[5] : null;
		//$output = self::output($post_id, $attr, $content, $tag);
		return $output;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_action('wpcf7_before_send_mail', [__CLASS__, 'wpcf7_before_send_mail'], 10, 3);
		add_filter('do_shortcode_tag', [__CLASS__, 'do_shortcode_tag'], 10, 4);
		add_filter('ifcf7_free_text_value', [__CLASS__, 'ifcf7_free_text_value'], 10, 2);
		add_filter('shortcode_atts_wpcf7', [__CLASS__, 'shortcode_atts_wpcf7'], 10, 3);
		add_filter('wpcf7_form_elements', [__CLASS__, 'wpcf7_form_elements']);
		add_filter('wpcf7_form_hidden_fields', [__CLASS__, 'wpcf7_form_hidden_fields']);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function shortcode_atts_wpcf7($out, $pairs, $atts){
		if(isset($atts['post_id'])){
			$out['post_id'] = $atts['post_id'];
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
		$post_id = self::get_post_id($contact_form, $submission);
		if(is_wp_error($post_id)){
			$submission->set_response($post_id->get_error_message());
			$submission->set_status('aborted'); // try to prevent conflicts with other plugins
			return;
		}
		$response = 'post' === get_post_type($post_id) ? __('Post updated.') : __('Item updated.');
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
		Storage::store_submission('update', 'post', $post_id, $contact_form, $submission);
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_form_elements($elements){
		if(!self::is_action()){
			return $elements;
		}
		$post_id = self::get_post_id();
		if(is_wp_error($post_id)){
			return $elements;
		}
		wp_reset_postdata();
		return $elements;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_form_hidden_fields($hidden_fields){
		global $post;
		if(!self::is_action()){
			return $hidden_fields;
		}
		$post_id = self::get_post_id();
		if(is_wp_error($post_id)){
			return $hidden_fields;
		}
		$hidden_fields['ifcf7_nonce'] = wp_create_nonce('ifcf7-edit-post_' . $post_id);
		$hidden_fields['ifcf7_post_id'] = $post_id;
		$post = get_post($post_id);
		setup_postdata($post);
		return $hidden_fields;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

<?php namespace IFCF7;

final class Reset_Password {

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
		return ('reset_password' === $action);
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
		if(!isset($tags['pass1'])){
			$missing[] = 'pass1';
		}
		if($missing){
			$error = current_user_can('manage_options') ? sprintf(__('Missing parameter(s): %s'), implode(', ', $missing)) . '.' : __('Something went wrong.');
			return '<div class="alert alert-danger" role="alert">' . $error . '</div>';
		}
		$invalid = [];
		if(isset($tags['pass1']) and $tags['pass1'] !== 'password*'){
			$invalid[] = 'pass1';
		}
        if(isset($tags['pass2']) and $tags['pass2'] !== 'password*'){
			$invalid[] = 'pass2';
		}
		if($invalid){
			$error = current_user_can('manage_options') ? sprintf(__('Invalid parameter(s): %s'), implode(', ', $invalid)) . '.' : __('Something went wrong.');
			return '<div class="alert alert-danger" role="alert">' . $error . '</div>';
		}
		if(is_user_logged_in()){
			$error = __('You are logged in already. No need to register again!');
			return '<div class="alert alert-warning" role="alert">' . $error . '</div>';
		}

        // validar mensajes
        if(empty(self::$post_id)){
            return '<div class="alert alert-danger" role="alert">' . 'No ID set.' . '</div>';
        }
        if(!in_the_loop()){
            return '<div class="alert alert-danger" role="alert">' . 'Outside the loop.' . '</div>';
        }
        if(self::$post_id !== get_the_ID()){
            return '<div class="alert alert-danger" role="alert">' . 'Page ID does not match.' . '</div>';
        }



		return $output;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
        add_action('login_form_resetpass', [__CLASS__, 'custom_form']);
        add_action('login_form_rp', [__CLASS__, 'custom_form']);
        add_action('parse_query', [__CLASS__, 'parse_query']);
		add_action('wpcf7_before_send_mail', [__CLASS__, 'wpcf7_before_send_mail'], 10, 3);
		add_filter('do_shortcode_tag', [__CLASS__, 'do_shortcode_tag'], 10, 4);
        add_filter('wpcf7_form_hidden_fields', [__CLASS__, 'wpcf7_form_hidden_fields']);
        add_filter('wpcf7_posted_data', [__CLASS__, 'wpcf7_posted_data']);
        add_filter('wpcf7_validate_password*', [__CLASS__, 'wpcf7_validate_password'], 20, 2);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function parse_query(&$query){
        if(empty(self::$post_id)){
            return;
        }
        if(self::$post_id !== $query->queried_object_id){
    		return;
    	}
        $rp_cookie = 'wp-resetpass-' . COOKIEHASH;
    	if(isset($_GET['key']) and isset($_GET['login'])){
    		$value = sprintf('%s:%s', wp_unslash($_GET['login']), wp_unslash($_GET['key']));
    		setcookie($rp_cookie, $value, 0, '/', COOKIE_DOMAIN, is_ssl(), true);
    		wp_safe_redirect(remove_query_arg(['key', 'login']));
    		exit;
    	}
        if(isset($_COOKIE[$rp_cookie]) and 0 < strpos($_COOKIE[$rp_cookie], ':')){
    		list($rp_login, $rp_key) = explode(':', wp_unslash($_COOKIE[$rp_cookie]), 2);
    		$user = check_password_reset_key($rp_key, $rp_login);
    	} else {
    		$user = false;
    	}
        if(!$user or is_wp_error($user)){
    		setcookie($rp_cookie, ' ', time() - YEAR_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true);
    		if($user and $user->get_error_code() === 'expired_key'){
    			wp_redirect(site_url('wp-login.php?action=lostpassword&error=expiredkey'));
    		} else {
    			wp_redirect(site_url('wp-login.php?action=lostpassword&error=invalidkey'));
    		}
    		exit;
    	}
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

        $pass1 = self::get_posted_data('pass1');
        $pass2 = self::get_posted_data('pass2');
        $posted_rp_key = self::get_posted_data('rp_key');

        $rp_cookie = 'wp-resetpass-' . COOKIEHASH;
        if(isset($_COOKIE[$rp_cookie]) and 0 < strpos($_COOKIE[$rp_cookie], ':')){
            list($rp_login, $rp_key) = explode(':', wp_unslash($_COOKIE[$rp_cookie]), 2);
            $user = check_password_reset_key($rp_key, $rp_login);

            if(!hash_equals($rp_key, $posted_rp_key)){
				$user = false;
			}

        } else {
            $user = false;
        }
        if(!$user or is_wp_error($user)){
            setcookie($rp_cookie, ' ', time() - YEAR_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true);
            if($user and $user->get_error_code() === 'expired_key'){
                $submission->set_response(__('<strong>Error</strong>: Your password reset link has expired. Please request a new link below.'));
            } else {
                $submission->set_response(__('<strong>Error</strong>: Your password reset link appears to be invalid. Please request a new link below.'));
            }
            $submission->set_status('aborted'); // try to prevent conflicts with other plugins
			return;
        }

        reset_password( $user, $pass1 );
        setcookie($rp_cookie, ' ', time() - YEAR_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true);

        //log in

		$response = __( 'Your password has been reset.' );
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
        do_action('ifcf7_password_reset', $user->ID, $contact_form, $submission);
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_form_hidden_fields($hidden_fields){
		if(!self::is_action()){
			return $hidden_fields;
		}
        $rp_cookie = 'wp-resetpass-' . COOKIEHASH;
        if(isset($_COOKIE[$rp_cookie]) and 0 < strpos($_COOKIE[$rp_cookie], ':')){
            list($rp_login, $rp_key) = explode(':', wp_unslash($_COOKIE[$rp_cookie]), 2);
            $hidden_fields['rp_key'] = $rp_key;
        }
		return $hidden_fields;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_posted_data($posted_data){
		if(!self::is_action()){
			return $posted_data;
		}
		$fields = ['pass1', 'pass2', 'rp_key'];
		foreach($fields as $field){
			if(isset($posted_data[$field])){
				self::$posted_data[$field] = $posted_data[$field];
				unset($posted_data[$field]);
			}
		}
		return $posted_data;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_validate_password($result, $tag){
        if(!in_array($tag->name, ['pass1', 'pass2'])){
			return $result;
		}
		if(!self::is_action()){
			return $result;
		}
        $pass1 = self::get_posted_data('pass1');
		$pass2 = self::get_posted_data('pass2');
        switch($tag->name){
            case 'pass1':
                if(false !== strpos(wp_unslash($pass1), '\\')){
                    $message = __('<strong>Error</strong>: Passwords may not contain the character "\\".');
                    $result->invalidate($tag, wp_strip_all_tags($message));
                    return $result;
                }
                break;
            case 'pass2':
                if($pass1 !== $pass2){
                    $message = __('<strong>Error</strong>: Passwords don&#8217;t match. Please enter the same password in both password fields.');
                    $result->invalidate($tag, wp_strip_all_tags($message));
                    return $result;
                }
                break;
        }
		return $result;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

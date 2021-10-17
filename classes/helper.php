<?php namespace IFCF7;

final class Helper {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static $meta_data = [], $posted_data = [];

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static function sanitize_posted_data($value){
        if(is_array($value)){
			$value = array_map([__CLASS__, 'sanitize_posted_data'], $value);
		} elseif(is_string($value)){
			$value = wp_check_invalid_utf8($value);
			$value = wp_kses_no_null($value);
		}
		return $value;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function setup_meta_data(){
        if(!empty(self::$meta_data)){
            return;
        }
        $contact_form = wpcf7_get_current_contact_form();
        if(null === $contact_form){
            return;
        }
        $submission = \WPCF7_Submission::get_instance();
        if(null === $submission){
            return;
        }
        self::$meta_data = [
            'contact_form_id' => $contact_form->id(),
            'contact_form_locale' => $contact_form->locale(),
            'contact_form_name' => $contact_form->name(),
            'contact_form_title' => $contact_form->title(),
            'container_post_id' => $submission->get_meta('container_post_id'),
            'current_user_id' => $submission->get_meta('current_user_id'),
            'remote_ip' => $submission->get_meta('remote_ip'),
            'remote_port' => $submission->get_meta('remote_port'),
            'submission_response' => $submission->get_response(),
            'submission_status' => $submission->get_status(),
            'timestamp' => $submission->get_meta('timestamp'),
            'unit_tag' => $submission->get_meta('unit_tag'),
            'url' => $submission->get_meta('url'),
            'user_agent' => $submission->get_meta('user_agent'),
        ];
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static function setup_posted_data(){
        if(!empty(self::$posted_data)){
            return;
        }
        $posted_data = array_filter((array) $_POST, function($key){
			return '_' !== substr($key, 0, 1);
		}, ARRAY_FILTER_USE_KEY);
        self::$posted_data = self::sanitize_posted_data($posted_data);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function get_action($contact_form = null){
        if(empty($contact_form)){
    		$contact_form = wpcf7_get_current_contact_form();
    	}
        if(empty($contact_form)){
    		return '';
    	}
    	$action = $contact_form->pref('action');
    	if(empty($action)){
    		return '';
    	}
    	return \WP_REST_Request::canonicalize_header_name($action);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function get_meta_data($name = ''){
        if(empty(self::$meta_data)){
            self::setup_meta_data();
        }
        if('' === $name){
            return self::$meta_data;
        }
        if(!isset($name, self::$meta_data)){
            return '';
        }
        return self::$meta_data[$name];
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function get_posted_data($name = ''){
    	if(empty(self::$posted_data)){
            self::setup_posted_data();
        }
        if('' === $name){
            return self::$posted_data;
        }
        if(!isset($name, self::$posted_data)){
            return '';
        }
        return self::$posted_data[$name];
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function invalid_fields($fields = [], $contact_form = null){
        if(empty($contact_form)){
    		$contact_form = wpcf7_get_current_contact_form();
    	}
        $invalid = [];
        if(empty($contact_form)){
    		return $invalid;
    	}
        $tags = wp_list_pluck($contact_form->scan_form_tags(), 'type', 'name');
        foreach($fields as $name => $type){
    		if(!empty($tags[$name])){
                if(!in_array($tags[$name], (array) $type)){
        			$invalid[] = $name;
        		}
    		}
    	}
        return $invalid;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function is($action = '', $contact_form = null){
    	return ($action === self::get_action($contact_form));
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function mail($contact_form = null, $submission = null){
        if(empty($contact_form)){
    		$contact_form = wpcf7_get_current_contact_form();
    	}
        if(empty($contact_form)){
    		return;
    	}
        if(empty($submission)){
            $submission = \WPCF7_Submission::get_instance();
        }
        if(empty($submission)){
            return;
        }
        $response = $submission->get_response();
        $status = $submission->get_status();
        if(self::skip_mail($contact_form)){
            if(empty($response)){
                $response = $contact_form->message('mail_sent_ok');
            }
            $status = 'mail_sent';
    	} else {
    		if(self::send_mail($contact_form)){
                $message = $contact_form->message('mail_sent_ok');
                if(empty($response)){
                    $response = $message;
                } else {
                    $response .= ' ' . $message;
                }
    			$status = 'mail_sent';
    		} else {
                $message = $contact_form->message('mail_sent_ng');
                if(empty($response)){
                    $response = $message;
                } else {
                    $response .= ' ' . $message;
                }
                $status = 'mail_failed';
    		}
    	}
        $submission->set_response(wp_strip_all_tags($response));
        $submission->set_status($status);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function missing_fields($fields = [], $contact_form = null){
        if(empty($contact_form)){
    		$contact_form = wpcf7_get_current_contact_form();
    	}
        $missing = [];
        if(empty($contact_form)){
    		return $missing;
    	}
        $tags = wp_list_pluck($contact_form->scan_form_tags(), 'type', 'name');
        foreach(array_keys($fields) as $name){
    		if(empty($tags[$name])){
    			$missing[] = $name;
    		}
    	}
        return $missing;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function send_mail($contact_form = null){
        if(null === $contact_form){
            $contact_form = wpcf7_get_current_contact_form();
        }
        if(null === $contact_form){
            return false;
        }
        $skip_mail = self::skip_mail($contact_form);
        if($skip_mail){
        	return true;
        }
        $result = \WPCF7_Mail::send($contact_form->prop('mail'), 'mail');
        if(!$result){
            return false;
        }
        $additional_mail = [];
    	if($mail_2 = $contact_form->prop('mail_2') and $mail_2['active']){
    		$additional_mail['mail_2'] = $mail_2;
    	}
    	$additional_mail = apply_filters('wpcf7_additional_mail', $additional_mail, $contact_form);
    	foreach($additional_mail as $name => $template){
    		\WPCF7_Mail::send($template, $name);
    	}
    	return true;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function skip_mail($contact_form = null){
        if(null === $contact_form){
            $contact_form = wpcf7_get_current_contact_form();
        }
        if(null === $contact_form){
            return false;
        }
        $skip_mail = ($contact_form->in_demo_mode() or $contact_form->is_true('skip_mail') or !empty($contact_form->skip_mail));
        $skip_mail = apply_filters('wpcf7_skip_mail', $skip_mail, $contact_form);
        return (bool) $skip_mail;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

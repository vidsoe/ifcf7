<?php namespace IFCF7;

final class Intl_Tel_Input {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function tel_html($html = '', $tag = null){
        $html = str_get_html($html);
        $wrapper = $html->find('.wpcf7-form-control-wrap', 0);
        $input = $wrapper->find('input', 0);
        $input->addClass('form-control ifcf7-intl-tel-input');
        $input->outertext = $input->outertext . '<input type="hidden" value="' . $input->value . '" name="' . $input->name . '_intl" class="ifcf7-intl-tel-input-hidden">';
        return $html;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function form_tag_html($html, $tag, $type, $basetype, $html_orig){
		if('tel' !== $type){
			return $html;
		}
		if(!$tag->has_option('intl')){
			return $html;
		}
		if(!function_exists('str_get_html')){
			require_once(plugin_dir_path(Loader::get_file()) . 'includes/simple-html-dom-1.9.1.php');
		}
		$html = self::tel_html($html_orig, $tag);
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_action('wp_enqueue_scripts', [__CLASS__, 'wp_enqueue_scripts']);
		add_filter('ifcf7_form_tag_html', [__CLASS__, 'form_tag_html'], 30, 5);
		add_filter('wpcf7_validate_tel*', [__CLASS__, 'wpcf7_validate_tel'], 20, 2);
		add_filter('wpcf7_validate_tel', [__CLASS__, 'wpcf7_validate_tel'], 20, 2);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wp_enqueue_scripts(){
		wp_enqueue_script('intl-tel-input', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js', ['contact-form-7'], '17.0.8', true);
		Loader::enqueue_asset('intl-tel-input', 'intl-tel-input.js', ['jquery']);
		Loader::add_inline_script('intl-tel-input', 'ifcf7_intl_tel_input.load();');
		wp_enqueue_style('intl-tel-input', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css', ['contact-form-7'], '17.0.8');
		Loader::enqueue_asset('intl-tel-input', 'intl-tel-input.css');
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function wpcf7_validate_tel($result, $tag){
		if(!$tag->has_option('intl')){
			return $result;
		}
		if(!preg_match('/^\+[1-9]\d{1,14}$/', Helper::get_posted_data($tag->name . '_intl'))){
			$result->invalidate($tag, wp_strip_all_tags('Invalid phone number.'));
			return $result;
		}
		return $result;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

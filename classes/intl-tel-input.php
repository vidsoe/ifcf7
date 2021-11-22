<?php namespace IFCF7;

final class Intl_Tel_Input {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static function placeholder_value($tag = null, $fallback = ''){
        if(empty($tag->values)){
            return $fallback;
        }
        if(in_array($tag->basetype, ['date', 'email', 'number', 'password', 'tel', 'text', 'textarea', 'url'])){
            if(!$tag->has_option('placeholder') and !$tag->has_option('watermark')){
                return $fallback;
            }
            return (string) reset($tag->values);
        } elseif('select' === $tag->basetype){
            if(!$tag->has_option('first_as_label')){
                return $fallback;
            }
            return (string) reset($tag->values);
        }
        return $fallback;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function tel_html($html = '', $tag = null){
        $html = str_get_html($html);
        $wrapper = $html->find('.wpcf7-form-control-wrap', 0);
        $input = $wrapper->find('input', 0);
        $input->addClass('form-control ifcf7-intl-tel-input');
        $input->outertext = $input->outertext . '<input type="hidden" name="' . $input->name . '_intl" class="ifcf7-intl-tel-input-hidden">';
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
		add_action('wpcf7_enqueue_scripts', [__CLASS__, 'wpcf7_enqueue_scripts']);
		add_action('wpcf7_enqueue_styles', [__CLASS__, 'wpcf7_enqueue_styles']);
		add_filter('ifcf7_form_tag_html', [__CLASS__, 'form_tag_html'], 30, 5);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_enqueue_scripts(){
		wp_enqueue_script('intl-tel-input', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js', ['contact-form-7'], '17.0.8', true);
		Loader::enqueue_asset('intl-tel-input', 'intl-tel-input.js', ['jquery']);
		Loader::add_inline_script('intl-tel-input', 'ifcf7_intl_tel_input.load();');
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_enqueue_styles(){
		wp_enqueue_style('intl-tel-input', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css', ['contact-form-7'], '17.0.8');
		Loader::enqueue_asset('intl-tel-input', 'intl-tel-input.css');
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

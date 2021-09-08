<?php namespace IFCF7;

final class Select2 {

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

	private static function select_html($html = '', $tag = null){
		$html = str_get_html($html);
		$wrapper = $html->find('.wpcf7-form-control-wrap', 0);
		$select = $wrapper->find('select', 0);
		$select->addClass('form-control ifcf7-select2');
		$data_attr = 'data-placeholder';
		if($tag->has_option('include_blank')){
			$select->{$data_attr} = '---';
		} else {
			$select->{$data_attr} = self::placeholder_value($tag);
		}
        return $html;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function form_tag_html($html, $tag, $type, $basetype, $html_orig){
		if('select' !== $type){
			return $html;
		}
		if(!$tag->has_option('select2')){
			return $html;
		}
		if(!$tag->has_option('include_blank') and empty(self::placeholder_value($tag))){
			return $html;
		}
		if(!function_exists('str_get_html')){
			require_once(plugin_dir_path(Loader::get_file()) . 'includes/simple-html-dom-1.9.1.php');
		}
		$html = self::select_html($html_orig, $tag);
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
		wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js', ['contact-form-7'], '4.0.13', true);
		Loader::enqueue_asset('select2', 'select2.js', ['select2']);
		Loader::add_inline_script('select2', 'ifcf7_select2.load();');
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_enqueue_styles(){
		wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css', ['contact-form-7'], '4.0.13');
		Loader::enqueue_asset('select2', 'select2.css', ['select2']);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

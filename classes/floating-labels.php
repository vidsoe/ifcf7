<?php namespace IFCF7;

final class Floating_Labels {

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
		$placeholder = self::placeholder_value($tag);
		$html = str_get_html($html);
		$wrapper = $html->find('.wpcf7-form-control-wrap', 0);
		$wrapper->addClass('ifcf7-floating-labels');
		$select = $wrapper->find('select', 0);
		$select->addClass('custom-select');
		if(!empty($select->find('option[selected]', 0)->value)){
			$select->addClass('placeholder-hidden');
		}
		$option = $select->find('option', 0);
		$option->innertext = '';
		$select->outertext = $select->outertext . '<label>' . $placeholder . '</label>';
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function text_html($html = '', $tag = null){
		$placeholder = self::placeholder_value($tag);
        $html = str_get_html($html);
		$wrapper = $html->find('.wpcf7-form-control-wrap', 0);
		$wrapper->addClass('ifcf7-floating-labels');
		$input = $wrapper->find('input', 0);
		$input->addClass('form-control');
		$input->outertext = $input->outertext . '<label>' . $placeholder . '</label>';
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function textarea_html($html = '', $tag = null){
		$placeholder = self::placeholder_value($tag);
        $html = str_get_html($html);
		$wrapper = $html->find('.wpcf7-form-control-wrap', 0);
		$textarea = $wrapper->find('textarea', 0);
		$textarea->addClass('form-control');
		$wrapper->addClass('ifcf7-floating-labels');
		$textarea->cols = null;
		$textarea->rows = null;
		$textarea->outertext = $textarea->outertext . '<label>' . $placeholder . '</label>';
        return $html;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function form_tag_html($html, $tag, $type, $basetype, $html_orig){
		$placeholder = self::placeholder_value($tag);
		if(empty($placeholder)){
			return $html;
		}
		if(!function_exists('str_get_html')){
			require_once(plugin_dir_path(Loader::get_file()) . 'includes/simple-html-dom-1.9.1.php');
        }
        switch($type){
            case 'date':
            case 'email':
            case 'number':
            case 'password':
            case 'tel':
            case 'text':
            case 'url':
                $html = self::text_html($html_orig, $tag);
                break;
            case 'select':
                $html = self::select_html($html_orig, $tag);
                break;
            case 'textarea':
                $html = self::textarea_html($html_orig, $tag);
                break;
        }
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_action('wpcf7_enqueue_scripts', [__CLASS__, 'wpcf7_enqueue_scripts']);
		add_action('wpcf7_enqueue_styles', [__CLASS__, 'wpcf7_enqueue_styles']);
		add_filter('ifcf7_form_tag_html', [__CLASS__, 'form_tag_html'], 20, 5);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_enqueue_scripts(){
		Loader::enqueue_asset('floating-labels', 'floating-labels.js', ['contact-form-7']);
		Loader::add_inline_script('floating-labels', 'ifcf7_floating_labels.load();');
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_enqueue_styles(){
		Loader::enqueue_asset('floating-labels', 'floating-labels.css', ['contact-form-7']);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

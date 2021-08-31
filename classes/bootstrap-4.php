<?php namespace IFCF7;

final class Bootstrap_4 {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function checkbox_html($html = '', $tag = null){
        $html = str_get_html($html);
        $type = in_array($tag->basetype, ['checkbox', 'radio']) ? $tag->basetype : 'checkbox';
		foreach($html->find('.wpcf7-list-item') as $li){
			$li->addClass('custom-control custom-' . $type);
			if($tag->has_option('inline')){
                $li->addClass('custom-control-inline');
            }
			$input = $li->find('input', 0);
			$input->addClass('custom-control-input');
            $id = $tag->name . '_' . str_replace('-', '_', sanitize_title($input->value));
			$input->id = $id;
			$label = $li->find('.wpcf7-list-item-label', 0);
			$label->addClass('custom-control-label');
			$label->for = $id;
			$label->tag = 'label';
            if($li->hasClass('has-free-text')){
                $freetext = $li->find('.wpcf7-free-text', 0);
                $freetext->addClass('form-control mt-1');
                $free_text_value = apply_filters('ifcf7_free_text_value', '', $tag->name);
                if(!empty($free_text_value)){
                    $freetext->value = $free_text_value;
                }
            }
			$li->innertext = $input->outertext . $label->outertext . $freetext;
		}
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function file_html($html = '', $tag = null){
        $html = str_get_html($html);
        $wrapper = $html->find('.wpcf7-form-control-wrap', 0);
        $wrapper->addClass('custom-file');
        $input = $wrapper->find('input', 0);
        $input->addClass('custom-file-input');
        if(empty($input->id)){
            $input->id = $tag->name;
        }
        if($tag->has_option('multiple')){
            $input->multiple = 'multiple';
            $input->name .= '[]';
        }
		$placeholder = self::placeholder_value($tag);
        if(empty($placeholder)){
            $placeholder = __('Select Files');
        }
        $input->outertext = $input->outertext . '<label class="custom-file-label" for="' . $input->id . '" data-browse="' . __('Select') . '">' . $placeholder . '</label>';
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static function placeholder_value($tag = null, $fallback = ''){
        if(empty($tag->values)){
            return $fallback;
        }
        if(in_array($tag->basetype, ['file'])){
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

	private static function range_html($html = '', $tag = null){
        $html = str_get_html($html);
        $wrapper = $html->find('.wpcf7-form-control-wrap', 0);
        $range = $wrapper->find('range', 0);
        $range->addClass('form-control-range');
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function select_html($html = '', $tag = null){
        $html = str_get_html($html);
        $wrapper = $html->find('.wpcf7-form-control-wrap', 0);
        $select = $wrapper->find('select', 0);
		$select->addClass('custom-select');
		if($tag->has_option('include_blank') and $tag->has_option('select2')){
            $select->addClass('ifcf7-select2');
        }
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function submit_html($html = '', $tag = null){
        $html = str_get_html($html);
        $submit = $html->find('input', 0);
        $submit->addClass('btn');
        if(false === strpos($submit->class, 'btn-')){
            $submit->addClass('btn-primary');
        }
        $submit->outertext = '<div class="ifcf7-submit-wrap d-flex align-items-center">' . $submit->outertext . '</div>';
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function text_html($html = '', $tag = null){
        $html = str_get_html($html);
        $wrapper = $html->find('.wpcf7-form-control-wrap', 0);
        $input = $wrapper->find('input', 0);
        $input->addClass('form-control');
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function textarea_html($html = '', $tag = null){
        $html = str_get_html($html);
        $wrapper = $html->find('.wpcf7-form-control-wrap', 0);
        $textarea = $wrapper->find('textarea', 0);
        $textarea->addClass('form-control');
        return $html;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function form_tag_html($html, $tag, $type, $basetype, $html_orig){
		if(!function_exists('str_get_html')){
			require_once(plugin_dir_path(Loader::get_file()) . 'includes/simple-html-dom-1.9.1.php');
        }
        switch($type){
            case 'acceptance':
            case 'checkbox':
            case 'radio':
                $html = self::checkbox_html($html_orig, $tag);
                break;
            case 'date':
            case 'email':
            case 'number':
            case 'password':
            case 'tel':
            case 'text':
            case 'url':
                $html = self::text_html($html_orig, $tag);
                break;
            case 'file':
                $html = self::file_html($html_orig, $tag);
                break;
            case 'range':
                $html = self::range_html($html_orig, $tag);
                break;
            case 'select':
                $html = self::select_html($html_orig, $tag);
                break;
            case 'submit':
                $html = self::submit_html($html_orig, $tag);
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
		add_filter('ifcf7_form_tag_html', [__CLASS__, 'form_tag_html'], 10, 5);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function wpcf7_enqueue_scripts(){
        wp_enqueue_script('bs-custom-file-input', 'https://cdn.jsdelivr.net/npm/bs-custom-file-input@1.3.4/dist/bs-custom-file-input.min.js', ['jquery'], '1.3.4', true);
        wp_add_inline_script('bs-custom-file-input', 'jQuery(function(){ bsCustomFileInput.init(); });');
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

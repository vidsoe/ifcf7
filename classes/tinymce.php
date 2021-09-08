<?php namespace IFCF7;

final class TinyMCE {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function textarea_html($html = '', $tag = null){
        $html = str_get_html($html);
		$wrapper = $html->find('.wpcf7-form-control-wrap', 0);
		$wrapper->addClass('d-none');
		$textarea = $wrapper->find('textarea', 0);
		ob_start();
		wp_editor(html_entity_decode($textarea->innertext), $tag->name, [
			'editor_class' => $textarea->class,
			'media_buttons' => false,
			'quicktags' => false,
			'textarea_rows' => 10,
			'tinymce'=> [
				'autoresize_min_height' => 210,
				'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,outdent,indent,sub,sup,undo,redo',
				'toolbar2' => '',
				'toolbar3' => '',
				'wp_autoresize_on' => true,
			],
		]);
		$textarea->outertext = ob_get_clean();
		$html .= '<div class="ifcf7-tinymce-loading-message ' . $tag->name . '">' . __('Loading&hellip;') . '</div><script>jQuery(function($){ tinymce.editors[\'' . $tag->name . '\'].on(\'init\', function(e){ $(\'.ifcf7-tinymce-loading-message.' . $tag->name . '\').addClass(\'d-none\'); $(\'.wpcf7-form-control-wrap.' . $tag->name . '\').removeClass(\'d-none\'); }); });</script>';
        return $html;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function form_tag_html($html, $tag, $type, $basetype, $html_orig){
		if('textarea' !== $type){
			return $html;
		}
		if(!$tag->has_option('tinymce')){
			return $html;
		}
		if(!function_exists('str_get_html')){
			require_once(plugin_dir_path(Loader::get_file()) . 'includes/simple-html-dom-1.9.1.php');
		}
		$html = self::textarea_html($html_orig, $tag);
        return $html;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_filter('ifcf7_form_tag_html', [__CLASS__, 'form_tag_html'], 30, 5);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

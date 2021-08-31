<?php namespace IFCF7;

final class Free_Text_Values {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static $additional_data = [], $data_options = [];

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static function free_text_value($name = ''){
        $name .= '_free_text';
        return Helper::get_posted_data($name);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function fix_posted_data($posted_data){
		if(empty(self::$additional_data)){
			return $posted_data;
		}
		$posted_data = array_merge((array) $posted_data, self::$additional_data);
		return $posted_data;
	}

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function fix_selectable_values($value, $value_orig, $tag){
		if(!wpcf7_form_tag_supports($tag->type, 'selectable-values')){
            return $value;
        }
        if(!$tag->has_option('free_text')){
            return $value;
        }
        $value = (array) $value;
        $value_orig = (array) $value_orig;
        $last_val = array_pop($value);
        list($tied_item) = array_slice(WPCF7_USE_PIPE ? $tag->pipes->collect_afters() : $tag->values, -1, 1);
        $tied_item = html_entity_decode($tied_item, ENT_QUOTES, 'UTF-8');
        if(0 === strpos($last_val, $tied_item)){
            $value[] = $tied_item;
            self::$additional_data[$tag->name . '_free_text'] = self::free_text_value($tag->name);
        } else {
            $value[] = $last_val;
			self::$additional_data[$tag->name . '_free_text'] = '';
        }
        self::$posted_data[$tag->name] = $value;
        return $value;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_filter('wpcf7_posted_data', [__CLASS__, 'fix_posted_data']);
        add_filter('wpcf7_posted_data_checkbox', [__CLASS__, 'fix_selectable_values'], 10, 3);
        add_filter('wpcf7_posted_data_checkbox*', [__CLASS__, 'fix_selectable_values'], 10, 3);
        add_filter('wpcf7_posted_data_radio', [__CLASS__, 'fix_selectable_values'], 10, 3);
        add_filter('wpcf7_posted_data_radio*', [__CLASS__, 'fix_selectable_values'], 10, 3);
        add_filter('wpcf7_posted_data_select', [__CLASS__, 'fix_selectable_values'], 10, 3);
        add_filter('wpcf7_posted_data_select*', [__CLASS__, 'fix_selectable_values'], 10, 3);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

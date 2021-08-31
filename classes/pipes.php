<?php namespace IFCF7;

final class Pipes {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static $additional_data = [], $data_options = [];

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
        if(WPCF7_USE_PIPE and $tag->pipes instanceof WPCF7_Pipes and !$tag->pipes->zero()){
        	self::$additional_data[$tag->name . '_value'] = $value;
        }
        return $value_orig;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_filter('wpcf7_posted_data', [__CLASS__, 'fix_posted_data']);
        add_filter('wpcf7_posted_data_checkbox', [__CLASS__, 'fix_selectable_values'], 20, 3);
        add_filter('wpcf7_posted_data_checkbox*', [__CLASS__, 'fix_selectable_values'], 20, 3);
        add_filter('wpcf7_posted_data_radio', [__CLASS__, 'fix_selectable_values'], 20, 3);
        add_filter('wpcf7_posted_data_radio*', [__CLASS__, 'fix_selectable_values'], 20, 3);
        add_filter('wpcf7_posted_data_select', [__CLASS__, 'fix_selectable_values'], 20, 3);
        add_filter('wpcf7_posted_data_select*', [__CLASS__, 'fix_selectable_values'], 20, 3);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

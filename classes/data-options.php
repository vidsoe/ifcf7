<?php namespace IFCF7;

final class Data_Options {

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

    public static function add_data_option($option = '', $data = []){
        if(!wpcf7_is_name($option) or !is_array($data)){
            return;
        }
        self::$data_options[$option] = $data;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function add_data_options($data, $options, $args){
        if(empty(self::$data_options)){
            return $data;
        }
        foreach($options as $option){
            if(!empty(self::$data_options[$option])){
                $data = array_merge((array) $data, self::$data_options[$option]);
            }
        }
		return $data;
    }

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
        $data = $tag->get_data_option();
        if(!$data){
            return $value;
        }
        $data_flip = array_flip($data);
        if(is_array($value)){
            $label = [];
            foreach($value as $key => $v){
                $label[$key] = $v;
                if(isset($data_flip[$v])){
                    $value[$key] = $data_flip[$v];
                }
            }
        } else {
            $label = $value;
            if(isset($data_flip[$value])){
                $value = $data_flip[$value];
            }
        }
        self::$additional_data[$tag->name . '_value'] = $value;
        return $label;
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
        add_filter('wpcf7_form_tag_data_option', [__CLASS__, 'add_data_options'], 10, 3);
		add_filter('wpcf7_posted_data', [__CLASS__, 'fix_posted_data']);
        add_filter('wpcf7_posted_data_checkbox', [__CLASS__, 'fix_selectable_values'], 30, 3);
        add_filter('wpcf7_posted_data_checkbox*', [__CLASS__, 'fix_selectable_values'], 30, 3);
        add_filter('wpcf7_posted_data_radio', [__CLASS__, 'fix_selectable_values'], 30, 3);
        add_filter('wpcf7_posted_data_radio*', [__CLASS__, 'fix_selectable_values'], 30, 3);
        add_filter('wpcf7_posted_data_select', [__CLASS__, 'fix_selectable_values'], 30, 3);
        add_filter('wpcf7_posted_data_select*', [__CLASS__, 'fix_selectable_values'], 30, 3);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

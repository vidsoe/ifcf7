<?php namespace IFCF7;

final class Shortcodes {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function do_shortcode_tag($output, $tag, $attr, $m){
		if('contact-form-7' !== $tag){
            return $output;
        }
        $contact_form = wpcf7_get_current_contact_form();
        if(null === $contact_form){
            return $output;
        }
        $output = apply_filters('ifcf7_shortcode_tag_html', $output, $tag, $attr, $m, $contact_form);
        return $output;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function if_func($atts, $content = ''){
        $atts = shortcode_atts([
            'compare' => '=',
    		'key' => '',
    		'type' => 'CHAR',
    		'value' => '',
        ], $atts, 'ifcf7_if');
    	extract($atts);
        if(!in_array($compare, ['!=', '<', '<=', '=', '>', '>=', 'EXISTS', 'LIKE', 'NOT EXISTS', 'NOT LIKE', 'NOT REGEXP', 'REGEXP'])){
            return '';
        }
        if(!in_array($type, ['CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'NUMERIC', 'TIME'])){
            return '';
        }
        $submission = \WPCF7_Submission::get_instance();
        if(null === $submission){
			return '';
		}
        $content = array_filter(explode('[ifcf7_else]', $content, 2));
        $content_false = isset($content[1]) ? $content[1] : '';
        $content_true = $content[0];
        $posted_data = $submission->get_posted_data($key);
        if(null === $posted_data){
            $posted_data = '';
        }
        if('' === ){
            switch($compare){
                case 'EXISTS':
                    if('' !== $posted_data){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                case 'NOT EXISTS':
                    if('' === $posted_data){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                default:
                    return '';
            }
        } else {
            switch($type){
                case 'DATE':
                    $posted_data = strtotime(date_i18n('Y-m-d', strtotime($posted_data)) . ' 00:00:00');
                    $value = strtotime(date_i18n('Y-m-d', strtotime($value)) . ' 00:00:00');
                    break;
                case 'DATETIME':
                    $posted_data = strtotime($posted_data);
                    $value = strtotime($value);
                    break;
                case 'DECIMAL':
                    $posted_data = (float) $posted_data;
                    $value = (float) $value;
                    break;
                case 'NUMERIC':
                    $posted_data = (int) $posted_data;
                    $value = (int) $value;
                    break;
                case 'TIME':
                    $posted_data = strtotime('1970-01-01 ' . date_i18n('H:i:s', strtotime($posted_data)));
                    $value = strtotime('1970-01-01 ' . date_i18n('H:i:s', strtotime($value)));
                    break;
                default:
                    $posted_data = (string) $posted_data;
                    $value = (string) $value;
            }
            switch($compare){
                case '!=':
                    if($posted_data !== $value){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                case '<':
                    if($posted_data < $value){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                case '<=':
                    if($posted_data <= $value){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                case '=':
                    if($posted_data === $value){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                case '>':
                    if($posted_data > $value){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                case '>=':
                    if($posted_data >= $value){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                case 'LIKE':
                    if(false !== strpos($posted_data, $value)){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                case 'NOT LIKE':
                    if(false === strpos($posted_data, $value)){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                case 'NOT REGEXP':
                    if(0 === preg_match($value, $posted_data)){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                case 'REGEXP':
                    if(1 === preg_match($value, $posted_data)){
                        return $content_true;
                    } else {
                        return $content_false;
                    }
                    break;
                default:
                    return '';
            }
        }
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_Action('init', [__CLASS__, 'register_shortcodes']);
		add_filter('do_shortcode_tag', [__CLASS__, 'do_shortcode_tag'], 10, 4);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function register_shortcodes(){
		add_shortcode('ifcf7_if', [__CLASS__, 'if_func']);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

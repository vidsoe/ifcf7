<?php namespace IFCF7;

final class Form_Tags {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function add_form_tags(){
		wpcf7_add_form_tag('acceptance', function($tag){
            $html = wpcf7_acceptance_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'acceptance', 'acceptance', $html);
            return $html;
        }, [
    		'name-attr' => true,
		]);
		wpcf7_add_form_tag(['checkbox', 'checkbox*'], function($tag){
            $html = wpcf7_checkbox_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'checkbox', 'checkbox', $html);
            return $html;
        }, [
			'multiple-controls-container' => true,
    		'name-attr' => true,
            'selectable-values' => true,
    	]);
		wpcf7_add_form_tag(['date', 'date*'], function($tag){
            $html = wpcf7_date_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'date', 'date', $html);
            return $html;
        }, [
    		'name-attr' => true,
    	]);
        wpcf7_add_form_tag(['email', 'email*'], function($tag){
            $html = wpcf7_text_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'email', 'text', $html);
            return $html;
        }, [
    		'name-attr' => true,
    	]);
		wpcf7_add_form_tag(['file', 'file*'], function($tag){
            $html = wpcf7_file_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'file', 'file', $html);
            return $html;
        }, [
			'file-uploading' => true,
    		'name-attr' => true,
    	]);
		wpcf7_add_form_tag(['number', 'number*'], function($tag){
            $html = wpcf7_number_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'number', 'number', $html);
            return $html;
        }, [
    		'name-attr' => true,
    	]);
        wpcf7_add_form_tag(['password', 'password*'], function($tag){
            $html = wpcf7_text_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'password', 'text', $html);
            return $html;
        }, [
    		'name-attr' => true,
    	]);
        wpcf7_add_form_tag(['radio', 'radio*'], function($tag){
            $html = wpcf7_checkbox_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'radio', 'checkbox', $html);
            return $html;
        }, [
			'multiple-controls-container' => true,
    		'name-attr' => true,
            'selectable-values' => true,
    	]);
		wpcf7_add_form_tag(['range', 'range*'], function($tag){
			$html = wpcf7_number_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'range', 'number', $html);
            return $html;
        }, [
    		'name-attr' => true,
    	]);
		wpcf7_add_form_tag(['select', 'select*'], function($tag){
            $html = wpcf7_select_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'select', 'select', $html);
            return $html;
        }, [
    		'name-attr' => true,
            'selectable-values' => true,
    	]);
        wpcf7_add_form_tag(['submit'], function($tag){
            $html = wpcf7_submit_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'submit', 'submit', $html);
            return $html;
        });
        wpcf7_add_form_tag(['tel', 'tel*'], function($tag){
            $html = wpcf7_text_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'tel', 'text', $html);
            return $html;
        }, [
    		'name-attr' => true,
    	]);
        wpcf7_add_form_tag(['text', 'text*'], function($tag){
            $html = wpcf7_text_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'text', 'text', $html);
            return $html;
        }, [
    		'name-attr' => true,
    	]);
        wpcf7_add_form_tag(['textarea', 'textarea*'], function($tag){
            $html = wpcf7_textarea_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'textarea', 'textarea', $html);
            return $html;
        }, [
    		'name-attr' => true,
    	]);
        wpcf7_add_form_tag(['url', 'url*'], function($tag){
            $html = wpcf7_text_form_tag_handler($tag);
            $html = apply_filters('ifcf7_form_tag_html', $html, $tag, 'url', 'text', $html);
            return $html;
        }, [
    		'name-attr' => true,
    	]);
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_action('wpcf7_init', [__CLASS__, 'add_form_tags']);
		remove_action('wpcf7_init', 'wpcf7_add_form_tag_acceptance');
		remove_action('wpcf7_init', 'wpcf7_add_form_tag_checkbox');
		remove_action('wpcf7_init', 'wpcf7_add_form_tag_date');
		remove_action('wpcf7_init', 'wpcf7_add_form_tag_file');
		remove_action('wpcf7_init', 'wpcf7_add_form_tag_number');
		remove_action('wpcf7_init', 'wpcf7_add_form_tag_select');
        remove_action('wpcf7_init', 'wpcf7_add_form_tag_submit');
		remove_action('wpcf7_init', 'wpcf7_add_form_tag_text');
		remove_action('wpcf7_init', 'wpcf7_add_form_tag_textarea');
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

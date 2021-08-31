<?php namespace IFCF7;

final class ACE {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load(){
		add_action('admin_enqueue_scripts', [__CLASS__, 'admin_enqueue_scripts']);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function admin_enqueue_scripts($hook_suffix){
		if(false === strpos($hook_suffix, 'wpcf7')){
			return;
		}
		wp_enqueue_script('ace', 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.min.js', ['wpcf7-admin'], '1.4.12', true);
		wp_enqueue_script('ace-language-tools', 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ext-language_tools.min.js', ['ace'], '1.4.12', true);
		Loader::enqueue_asset('ace', 'ace.css', ['contact-form-7-admin']);
		Loader::enqueue_asset('ace', 'ace.js', ['ace-language-tools']);
		Loader::add_inline_script('ace', 'ifcf7_ace.load();');
    }

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

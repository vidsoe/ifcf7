<?php namespace IFCF7;

final class Loader {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private static $file = '';

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function add_inline_script($handle = '', $data = '', $position = 'after'){
		if(0 !== strpos($handle, 'ifcf7_')){
			$handle = 'ifcf7_' . $handle;
		}
		return wp_add_inline_script($handle, $data, $position);
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function enqueue_asset($handle = '', $src = '', $deps = []){
		if(0 !== strpos($handle, 'ifcf7_')){
			$handle = 'ifcf7_' . $handle;
		}
		$filename = basename($src);
		$file = plugin_dir_path(self::$file) . 'assets/' . $filename;
		if(!file_exists($file)){
			return;
		}
		$src = plugin_dir_url(self::$file) . 'assets/' . $filename;
		$ver = filemtime($file);
		$mimes = [
			'css' => 'text/css',
			'js' => 'application/javascript',
		];
		$filetype = wp_check_filetype($filename, $mimes);
		switch($filetype['type']){
			case 'application/javascript':
				wp_enqueue_script($handle, $src, $deps, $ver, true);
				break;
			case 'text/css':
				wp_enqueue_style($handle, $src, $deps, $ver);
				break;
		}
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function get_file(){
    	return self::$file;
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function load($file = ''){
    	self::$file = $file;
		add_action('plugins_loaded', [__CLASS__, 'plugins_loaded']);
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public static function plugins_loaded(){
    	if(!function_exists('is_plugin_active')){
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        if(is_plugin_active('vidsoe/vidsoe.php')){
            vidsoe()->build_update_checker('https://github.com/vidsoe/ifcf7', self::$file, 'ifcf7');
        }
		if(!is_plugin_active('contact-form-7/wp-contact-form-7.php')){
            return;
        }
		foreach(glob(plugin_dir_path(self::$file) . 'classes/*.php') as $file){
			$class = basename($file, '.php');
			if('loader' === $class){
				continue;
			}
			$class = __NAMESPACE__ . '\\' . str_replace('-', '_', $class);
			require_once($file);
			if(is_callable([$class, 'load'])){
				call_user_func([$class, 'load']);
			}
		}
        $stylesheet_directory = get_stylesheet_directory();
        if(@is_dir($stylesheet_directory. '/ifcf7/classes')){
            foreach(glob($stylesheet_directory . '/ifcf7/classes/*.php') as $file){
    			$class = basename($file, '.php');
    			if('loader' === $class){
    				continue;
    			}
    			$class = __NAMESPACE__ . '\\' . str_replace('-', '_', $class);
    			require_once($file);
    			if(is_callable([$class, 'load'])){
    				call_user_func([$class, 'load']);
    			}
    		}
        }
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

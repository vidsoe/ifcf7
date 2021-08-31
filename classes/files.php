<?php namespace IFCF7;

final class Files {

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// private
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function copy($source = '', $destination = '', $overwrite = false, $mode = false){
		global $wp_filesystem;
		$fs = self::filesystem();
		if(is_wp_error($fs)){
			return $fs;
		}
		if(!$wp_filesystem->copy($source, $destination, $overwrite, $mode)){
			return new \WP_Error('ifcf7_error', sprintf(__('The uploaded file could not be moved to %s.'), $destination));
		}
		return $destination;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function filesystem(){
		global $wp_filesystem;
		if($wp_filesystem instanceof WP_Filesystem_Direct){
			return true;
		}
		if(!function_exists('get_filesystem_method')){
			require_once(ABSPATH . 'wp-admin/includes/file.php');
		}
		if('direct' !== get_filesystem_method()){
			return new \WP_Error('ifcf7_error', __('Could not access filesystem.'));
		}
		if(!WP_Filesystem()){
			return new \WP_Error('ifcf7_error', __('Filesystem error.'));
		}
		return true;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function in_uploads($file = ''){
		$upload_dir = wp_get_upload_dir();
		return (0 === strpos($file, $upload_dir['basedir']));
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function maybe_generate_attachment_metadata($attachment_id = 0){
		$attachment = get_post($attachment_id);
		if(null === $attachment){
			return false;
		}
		if('attachment' !== $attachment->post_type){
			return false;
		}
		wp_raise_memory_limit('image');
		if(!function_exists('wp_generate_attachment_metadata')){
			require_once(ABSPATH . 'wp-admin/includes/image.php');
		}
		wp_maybe_generate_attachment_metadata($attachment);
		return true;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function move_uploaded_file($tmp_name = ''){
		global $wp_filesystem;
		$fs = self::filesystem();
		if(is_wp_error($fs)){
			return $fs;
		}
		if(!$wp_filesystem->exists($tmp_name)){
			return new \WP_Error('ifcf7_error', __('File does not exist! Please double check the name and try again.'));
		}
		$upload_dir = wp_upload_dir();
		$original_filename = wp_basename($tmp_name);
		$filename = wp_unique_filename($upload_dir['path'], $original_filename);
		$file = trailingslashit($upload_dir['path']) . $filename;
		$result = self::copy($tmp_name, $file);
		if(is_wp_error($result)){
			return $result;
		}
		return $file;
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	private static function upload($file = '', $post_id = 0){
		global $wp_filesystem;
		$fs = self::filesystem();
		if(is_wp_error($fs)){
			return $fs;
		}
		if(!$wp_filesystem->exists($file)){
			return new \WP_Error('ifcf7_error', __('File does not exist! Please double check the name and try again.'));
		}
		if(!self::in_uploads($file)){
			return new \WP_Error('ifcf7_error', sprintf(__('Unable to locate needed folder (%s).'), __('The uploads directory')));
		}
		$filename = wp_basename($file);
		$filetype_and_ext = wp_check_filetype_and_ext($file, $filename);
		if(!$filetype_and_ext['type']){
			return new \WP_Error('ifcf7_error', __('Sorry, this file type is not permitted for security reasons.'));
		}
		$upload_dir = wp_get_upload_dir();
		$attachment_id = wp_insert_attachment([
			'guid' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file),
			'post_mime_type' => $filetype_and_ext['type'],
			'post_status' => 'inherit',
			'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
		], $file, $post_id, true);
		if(is_wp_error($attachment_id)){
			return $attachment_id;
		}
		self::maybe_generate_attachment_metadata($attachment_id);
		return $attachment_id;
	}


    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//
	// public
	//
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

	public static function upload_file($tmp_name = '', $post_id = 0){
		$file = self::move_uploaded_file($tmp_name);
		if(is_wp_error($file)){
			return $file;
		}
		return self::upload($file, $post_id);
	}

	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

}

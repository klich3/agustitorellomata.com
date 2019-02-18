<?php

class tooUpload {
	
	public static $tmp_dir = 'content/';
	public static $ext_allowed = array('jpg', 'jpeg', 'png', 'gif', 'JPG', 'JPEG', 'PNG', 'GIF');
	public static $html_input_name = 'files';
	
	/**
	 * initUploadFiles function.
	 * 
	 * @access public
	 * @static
	 * @param mixed $fn_files
	 * @param mixed $fn_args
	 * @return void
	 */
	public static function initUploadFiles($fn_files, $fn_args)
	{
		//lookup folders
		if(sizeof($fn_args) == 0) return self::exit_status(400, 'No se encuentra la carpeta de destino :(');
		
		$fn_tmp_dir = self::$tmp_dir;
		
		if($fn_files[self::$html_input_name]['error'][0] == 1) return self::exit_status(400, 'El tamaño del archivo es demasiado grande!');
		
		if(array_key_exists(self::$html_input_name, $fn_files) && $fn_files[self::$html_input_name]['error'][0] == 0)
		{
			$fn_file = $_FILES[self::$html_input_name];
			$fn_now = microtime();
			$fn_new_name = md5("{$fn_now}-{$fn_file['name'][0]}");
			
			if(!in_array(self::get_extension($fn_file['name'][0]), self::$ext_allowed)) return self::exit_status(401, 'Solo estos archivos ('.implode(',', self::$ext_allowed).') están permitidos!');
			
			$fn_ext = explode('.', $fn_file['name'][0]);
			$fn_ext = end($fn_ext);
			
			if(move_uploaded_file($fn_file['tmp_name'][0], "{$fn_tmp_dir}{$fn_new_name}.{$fn_ext}"))
			{
				$fn_prefix_t = microtime();
				$fn_prefix = str_replace(' ', '-', $fn_prefix_t);
					
				$fn_resize = new cacheAndResize_onFly(array(
					'cacheDir' 			=> 'content/',
					'defaultQuality' 	=> 85,
					'cacheTime' 		=> 60*60*120*99999, //min
					'cropImages' 		=> true,
					'echoImage' 		=> false,
					'delOriginalFile' 	=> true,
					'filename_prefix'	=> $fn_prefix
				));
				
				//tamaños por defecto
				$fn_w = 450;
				$fn_h = null;
				
				$fn_resize->processImage("{$fn_tmp_dir}{$fn_new_name}.{$fn_ext}", $fn_w, $fn_h);
				
				return self::exit_status(200, 'Imagen subida y procesada!', array(
					'ext' => $fn_ext,
					'src' => "{$fn_tmp_dir}{$fn_new_name}.{$fn_ext}",
					'cachedSrc' => "content/".$fn_resize->echoImageName("{$fn_tmp_dir}{$fn_new_name}.{$fn_ext}"),
				));
			}
		}else{
			return self::exit_status(400, 'Algo va mal no puedo subir este archivo!');
		}
	}
	
	private static function exit_status($fn_status, $fn_str, $fn_data = '')
	{
		return array(
			'status' => $fn_status,
			'message' => $fn_str,
			'data' => $fn_data,
		);
	}
	
	private static function get_extension($fn_file_name)
	{
		$fn_ext = explode('.', $fn_file_name);
		$fn_ext = array_pop($fn_ext);
		
		return strtolower($fn_ext);
	}
}
	
?>
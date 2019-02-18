<?php

/**
 * Code created by Anthony Belyaev [dm211 - belyaev@dm211.com]
 *
 * Cache and Resize image on fly
 *
 */
 
 /*
	 -call url
	 	-check exist file (url -> md5) + _w + _h.extension
	 		1: check file timestamp
	 			0: 0*
	 			1: 1*
	 		0: 0*
	 		
	 0*:-download file
	 	-process
	 	-save (url -> md5) + _w + _h.extension
	 	-del tmp downloaded file
	 
	 1*:-headers, readfile and echo
 */
 
 /*
	include_once('class/resize.php');
	
	$fn_src = isset($_REQUEST['src']) ? $_GET['src'] : exit(400);
	$fn_w  = isset($_REQUEST['w']) ? $_GET['w'] : null;
	$fn_h = isset($_REQUEST['h']) ? $_GET['h'] : null;
	$fn_q = isset($_REQUEST['q']) ? $_GET['q'] : 85;
	
	$fn_resize = new cacheAndResize_onFly(array(
		'cacheDir' => $dm['cache-dir'],
		'defaultQuality' => 85,
		'cacheTime' => 1, //min
		'cropImages' => true,
		'echoImage' => true,
		'delOriginalFile' => true
	));
	
	$fn_resize->processImage($fn_src, $fn_w, $fn_h);
	exit;
 */

class cacheAndResize_onFly 
{
	public $cacheDir = "/cache/";
	public $cacheTime = 20;
	public $defaultQuality = 85;
	public $cropImages = TRUE;
	public $fileCachedName;
	public $echoImage = FALSE;
	public $originalFileName;
	public $delOriginalFile = TRUE;
	public $filename_prefix = ''; 
	
	public function __construct($fn_args)
	{
		//processs arguments array
		if(!empty($fn_args))
		{
			foreach($fn_args as $key => $property)
			{
				if(property_exists($this, $key)) $this->{$key} = $property;
			}
		}
		
		// Add structure directories
	    if(!is_dir($this->cacheDir)) mkdir($this->cacheDir, 0755, true);
	    if(!is_dir($this->cacheDir.'remote/')) mkdir($this->cacheDir.'remote/', 0755, true);
	    
	    //run cleaner
	    //$this->cleanCachedFiles();
	}
	
	/*
		muestra imagen
		@url
		@w
		@h
		@echo - send headers show image / return file name
	*/
	public function processImage($fn_src, $fn_w = NULL, $fn_h = NULL, $fn_crop = true)
	{
		if(!isset($fn_src)) return false;
		
		$this->cropImages = $fn_crop;
		
		//decode url string
		$fn_imagePath = urldecode($fn_src);
		$fn_purl = parse_url($fn_imagePath);
		$fn_finfo = pathinfo($fn_imagePath);

		//extension file
		$fn_ext = $fn_finfo['extension'];
		
		//filter file extensions
		if(!preg_match('/^(?:gif|jpg|jpeg|png)$/i', $fn_ext)) die('The image being resized is not a valid gif, jpg or png.');
		
		//check if exist cached file
		$if_checkFile = $this->checkFile($fn_src, $fn_w, $fn_h, $fn_ext);
		
		if($if_checkFile)
		{
			//existe
			if(!$this->echoImage)
			{
				return $this->echoImageName();
				exit;
			}else{
				$this->echoImageFile();
				exit;
			}
		}else{
			//no existe
			if($this->getFile($fn_imagePath, $fn_finfo))
			{
				//true
				$this->resizeImage($fn_w, $fn_h);				
			}else{
				//false
				die('archivo no encontrado, revise la ruta');
			}
		}
	}
	
	/*
		recorre dirictorio en busqueda de archivos caducados y los borra
	*/	
	private function cleanCachedFiles()
	{
		$ffs = scandir($this->cacheDir);
		
		foreach($ffs as $ff)
		{ 
	    	if($ff != '.' && $ff != '..' && $ff != 'remote' && $ff != 'index.html')
	        { 
	        	$local_filepath = ltrim($this->cacheDir.$ff, './');
	        	
	        	if(file_exists($local_filepath))
				{
					if(filemtime($local_filepath) > strtotime("+{$this->cacheTime} minutes")) @unlink($local_filepath);
				}
	        } 
	    }
	}
	
	/*
		comprueba si existe el archivo ya en cache y procesado
	*/
	private function checkFile($fn_src, $fn_w = NULL, $fn_h = NULL, $fn_ext)
	{
		$this_fileWidth = (isset($fn_w) || $fn_w = NULL) ? $fn_w : '';
		$this_fileHeight = (isset($fn_h) || $fn_h = NULL) ? $fn_h : '';
		
		$this->fileCachedName = $this->filename_prefix.md5($fn_src).'w_'.$this_fileWidth.'h_'.$this_fileHeight.'.'.$fn_ext;
		
		if(file_exists($this->cacheDir.$this->fileCachedName))
		{
			return true;
		}else{
			return false;
		}
	}
	
	private function getFile($fn_imagePath, $fn_finfo)
	{
		list($filename) = explode('?', $fn_finfo['basename']);
		$local_filepath = $this->cacheDir.'remote/'.$filename;
		
		//set original file to global vars
		$this->originalFileName = $filename;
		
		$fn_img = file_get_contents($fn_imagePath);
		file_put_contents($local_filepath, $fn_img);
		
		if(!$fn_img) return false;
		return true;
	}
	
	private function openImage($fn_mimeType, $fn_org_img_path)
	{
		switch($fn_mimeType)
	    {
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/pjgpg':
			    $fn_img_data = imagecreatefromjpeg($fn_org_img_path);
			break;
			
			case 'image/png':
			    $fn_img_data = imagecreatefrompng($fn_org_img_path);
			break;
			
			case 'image/gif':
			    $fn_img_data = imagecreatefromgif($fn_org_img_path);
			break;
		}
		
		return $fn_img_data;
	}
	
	private function resizeImage($fn_w = NULL, $fn_h = NULL)
	{
		$fn_org_img_path = $this->cacheDir.'remote/'.$this->originalFileName;
		
		$fn_mimeType = getimagesize($fn_org_img_path);
		$fn_mimeType = $fn_mimeType['mime'];
		
		$fn_img_data = $this->openImage($fn_mimeType, $fn_org_img_path);
		
		if($fn_w == NULL || $fn_h == NULL)
		{
			//get original file size w & h
			list($fn_org_width, $fn_org_height, $type, $attr) = getimagesize($fn_org_img_path);
			
			if($fn_h == NULL)
			{
				//set specific width & new height
				$image_height = floor(($fn_org_height/$fn_org_width)*$fn_w);
				$image_width  = $fn_w;
				
				$destHeight = round(imagesy($fn_img_data) * $image_width / imagesx($fn_img_data));
		        $dest = imagecreatetruecolor($image_width, $destHeight);
		        $dest2 = imagecreatetruecolor($image_width, $image_height);
		
		        $destHeight2 = ($destHeight - $image_height) / 2;
		        imageCopyResampled($dest, $fn_img_data, 0, 0, 0, 0, $image_width, $destHeight, imagesx($fn_img_data), imagesy($fn_img_data));
		        imagecopy($dest2, $dest, 0, 0, 0, $destHeight2, imagesx($dest), imagesy($dest));
			}else{
				//set specific height & new width
				$image_width  = floor(($fn_org_width/$fn_org_height)*$fn_h);
				$image_height = $fn_h;
				
				$destWidth = round(imagesx($fn_img_data) * $image_height / imagesy($fn_img_data));
		        $dest = imagecreatetruecolor($destWidth, $image_height);
		        $dest2 = imagecreatetruecolor($image_width, $image_height);
		
		        $destWidth2 = ($destWidth - $image_width) / 2;
		        imageCopyResampled($dest, $fn_img_data, 0, 0, 0, 0, $destWidth, $image_height, imagesx($fn_img_data), imagesy($fn_img_data));
		        imagecopy($dest2, $dest, 0, 0, $destWidth2, 0, imagesx($dest), imagesy($dest));
			}
		}else{
			//if user specif. w & h to resize image
		
			$fn_ratio = $fn_w / $fn_h;
		    $resSrc = imagesx($fn_img_data) / imagesy($fn_img_data);
		    
		    if($fn_ratio < $resSrc) 
		    {
		        $destWidth = round(imagesx($fn_img_data) * $fn_h / imagesy($fn_img_data));
		        $dest = imagecreatetruecolor($destWidth, $fn_h);
		        $dest2 = imagecreatetruecolor($fn_w, $fn_h);
		
		        $destWidth2 = ($destWidth - $fn_w) / 2;
		        imageCopyResampled($dest, $fn_img_data, 0, 0, 0, 0, $destWidth, $fn_h, imagesx($fn_img_data), imagesy($fn_img_data));
		        imagecopy($dest2, $dest, 0, 0, $destWidth2, 0, imagesx($dest), imagesy($dest));
		    }else{
		        $destHeight = round(imagesy($fn_img_data) * $fn_w / imagesx($fn_img_data));
		        $dest = imagecreatetruecolor($fn_w, $destHeight);
		        $dest2 = imagecreatetruecolor($fn_w, $fn_h);
		
		        $destHeight2 = ($destHeight - $fn_h) / 2;
		        imageCopyResampled($dest, $fn_img_data, 0, 0, 0, 0, $fn_w, $destHeight, imagesx($fn_img_data), imagesy($fn_img_data));
		        imagecopy($dest2, $dest, 0, 0, 0, $destHeight2, imagesx($dest), imagesy($dest));
		    }
		}		
		
		switch($fn_mimeType)
	    {
			case 'image/jpg':
	        case 'image/jpeg':
	        case 'image/pjgpg':
	            //save
	            @imagejpeg($dest2, $this->cacheDir.$this->fileCachedName, $this->defaultQuality);
	        break;
	        
	        case 'image/png':
	            //save
	            @imagepng($dest2, $this->cacheDir.$this->fileCachedName, floor( $this->defaultQuality * 0.09 ));
	        break;
	        
	        case 'image/gif':
	            //save
	            @imagegif($dest2, $this->cacheDir.$this->fileCachedName);
	        break;
	    }
			
		imagedestroy($dest2);
	    imagedestroy($dest);
	    imagedestroy($fn_img_data);
	    
	    //del original image
		if($this->delOriginalFile) @unlink($this->cacheDir.'remote/'.$this->originalFileName);
	    
	    //existe
		if(!$this->echoImage)
		{
			return $this->echoImageName();
			exit;
		}else{
			$this->echoImageFile();
			exit;
		}
	}
	
	/*
		lanza headers y muestra imagen
	*/
	private function echoImageFile()
	{
		$fn_file_size = getimagesize($this->cacheDir.$this->fileCachedName);
		
		//lunch headers
		if(! ($fn_file_size && $fn_file_size['mime'])) return false;
		header('Content-Type: '.$fn_file_size['mime']);
		
		$fn_size = filesize($this->cacheDir.$this->fileCachedName); 
		header("Content-Length: {$fn_size}");
		header("Cache-Control: max-age=290304000, public, must-revalidate, proxy-revalidate");
		header("Expires:Thu, 31 Dec 2013 20:00:00 GMT");
		header("Pragma: no-cache");
	    
	    //echo image
	    $fn_bytes = @readfile($this->cacheDir.$this->fileCachedName);
		$fn_image_content = @file_get_contents($this->cacheDir.$this->fileCachedName);
		
		if($this->echoImage)
		{
			echo $fn_image_content;
			return true;
		}
		
		return false;
	}
	
	/*
		devuelve nombre del archivo
	*/
	public function echoImageName()
	{
		return $this->fileCachedName;
	}
}

?>

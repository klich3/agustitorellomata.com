<?php
	
global $CONFIG, $fn_page_args, $db, $st_lang;


//home slider header image
$fn_home_gallery_q = $db->FetchValue("
	SELECT `objects`
	FROM `gallery`
	WHERE `id`=:i
	LIMIT 1;
", array(
	'i' => 11,
));

$fn_home_g_array = array();

if($fn_home_gallery_q && isJson($fn_home_gallery_q))
{
	$fn_home_gallery_q = json_decode($fn_home_gallery_q, true);
	
	foreach($fn_home_gallery_q as $gk => $gv)
	{
		if(isset($gv['img']))
		{
			$fn_reg = str_replace('/', '\/', $CONFIG['site']['base_script']);
			$fn_reg = (string)"/({$fn_reg})/";
			$fn_foto = (preg_match($fn_reg, $gv['img'])) ? $gv['img'] : $CONFIG['site']['base_script'].$gv['img'];
			
			$gv['img'] = $fn_foto;
		}
		
		$fn_home_g_array[] = $gv;
	}
}

//home slider small
$fn_home_gallery_small_q = $db->FetchValue("
	SELECT `objects`
	FROM `gallery`
	WHERE `id`=:i
	LIMIT 1;
", array(
	'i' => 12,
));

$fn_home_g_s_array = array();

if($fn_home_gallery_small_q && isJson($fn_home_gallery_small_q))
{
	$fn_home_gallery_small_q = json_decode($fn_home_gallery_small_q, true);
	
	foreach($fn_home_gallery_small_q as $gk => $gv)
	{
		if(isset($gv['img']))
		{
			$fn_reg = str_replace('/', '\/', $CONFIG['site']['base_script']);
			$fn_reg = (string)"/({$fn_reg})/";
			$fn_foto = (preg_match($fn_reg, $gv['img'])) ? $gv['img'] : $CONFIG['site']['base_script'].$gv['img'];
			
			$gv['img'] = $fn_foto;
		}
		
		$fn_home_g_s_array[] = $gv;
	}
}

$fn_xtemplate_parse['assign'][] = array(
	'jsonSlider' => json_encode(array(
		"config" => array(
			'dom' => 'header_slider',
			'type' => 'normal',	
			'showArrows' => false,
			'showDots' => true,
		),
		'data' => $fn_home_g_array,
	)),
	
	'jsonSliderSmall' => json_encode(array(
		"config" => array(
			'dom' => 'small_slider',
			'type' => 'normal',	
			'arrowsAlign' => 'bottom',
			'showArrows' => true,
			'showDots' => false,
		),
		'data' => $fn_home_g_s_array,
	)),
);
$fn_xtemplate_parse['parse'][] = '';

?>
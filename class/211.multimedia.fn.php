<?php

/**
 * get_youtube_videoStream function.
 * 
 * @access public
 * @param mixed $fn_video_id (default: NULL)
 * @return void
 */
function get_youtube_videoStream($fn_video_id = NULL)
{
	if($fn_video_id == NULL) return array(
		'status' => 400,
		'message' => 'falta atributo Video ID',
	);
	
	$fn_videos_url = array();
	
	parse_str(file_get_contents("http://www.youtube.com/get_video_info?video_id={$fn_video_id}"), $info);
	$streams = $info['url_encoded_fmt_stream_map'];
	
	$streams = explode(',', $streams);
	
	foreach($streams as $stream)
	{
	    parse_str($stream, $data);
		
		if(preg_match('/(hd720|medium)/', $data['quality']))
		{
			preg_match('/(.*?);/', $data['type'], $fn_codec);
			
			$fn_videos_url[] = array(
				'codec' => $fn_codec[1],
				'url' => $data['url'],
			);
		}
	}
	
	return $fn_videos_url;
}

/**
 * get_youtube_video function.
 * 
 * @access public
 * @param mixed $fn_full_url
 * @return void
 
 -{
"height": 270,
"title": "ВЕЗЕМ ГОЛЬФ В МОСКВУ. ЛЮТЫЙ CORRADO. GT86 ROCKET BUNNY ДАЕТ ШУМУ",
"provider_url": "https://www.youtube.com/",
"author_name": "LOW CARS MEET",
"thumbnail_url": "https://i.ytimg.com/vi/3oZdKgrwGzU/hqdefault.jpg",
"author_url": "https://www.youtube.com/channel/UCDcdwoIrn2M1mwfEz0NCODg",
"thumbnail_height": 360,
"version": "1.0",
"type": "video",
"html": "<iframe width=\"480\" height=\"270\" src=\"https://www.youtube.com/embed/3oZdKgrwGzU?feature=oembed\" frameborder=\"0\" allowfullscreen></iframe>",
"thumbnail_width": 480,
"provider_name": "YouTube",
"width": 480
}
 
 */
function get_youtube_video($fn_full_url)
{
	$fn_yt = "http://www.youtube.com/oembed?url=".$fn_full_url."&format=json";

	$curl = curl_init($fn_yt);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$fn_return = curl_exec($curl);
	curl_close($curl);
	$fn_return = json_decode($fn_return, true);

	return $fn_return;
}

/**
 * get_vimeo_video function.
 * 
 * @access public
 * @param mixed $fn_full_url
 * @return void
 
 -{
"type": "video",
"version": "1.0",
"provider_name": "Vimeo",
"provider_url": "https://vimeo.com/",
"title": "Headless",
"author_name": "Sebastian Sdaigui",
"author_url": "https://vimeo.com/sebastiansdaigui",
"is_plus": "1",
"html": "<iframe src=\"https://player.vimeo.com/video/212692070\" width=\"640\" height=\"360\" frameborder=\"0\" title=\"Headless\" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>",
"width": 640,
"height": 360,
"duration": 403,
"description": "A candid portrait of three hedonistic queer performers living in New York\n\nhttps://www.nowness.com/category/culture/headless-sebastian-sdaigui\n\n\nSTARRING:\nJean Carlos Pinae\nCesar Garcia \nInfinit Coles\n\nCREDITS :\nDIRECTOR : Sebastian Sdaigui \nPRODUCER: Alanna Harrington\nASSOCIATE PRODUCER: Chelsea Donini\nASSOCIATE PRODUCER: Carol Garlick\nASSISTANT DIRECTOR: Toryn SeaBrooks\nDIRECTOR OF PHOTOGRAPHY: Geoffrey Taylor\nDIRECTOR OF PHOTOGRAPHY: Conor Murphy\n1ST AC: Megaera Stephens\n1ST AC: Sam Wolff \nEDITOR: Jordan Rosenbloom\nBOOM OP: Fernando \"Frandy\" Castillo\nSOUND DESIGN: Mihir Chitale\nCOLORIST: Josh Bohoskey\nANIMATION: Rebecca Shapass\nFONTS: William Cooper\n\nMUSIC:\nPrincess Nokia \"Tomboy\"\nDeath Grip \"LSDXOXO\"\n\nPRODUCTION : GOODFORNOTHINGFILMS\n\nNOWNESS 2017",
"thumbnail_url": "https://i.vimeocdn.com/video/628751802_640.webp",
"thumbnail_width": 640,
"thumbnail_height": 360,
"thumbnail_url_with_play_button": "https://i.vimeocdn.com/filter/overlay?src0=https%3A%2F%2Fi.vimeocdn.com%2Fvideo%2F628751802_640.webp&src1=http%3A%2F%2Ff.vimeocdn.com%2Fp%2Fimages%2Fcrawler_play.png",
"upload_date": "2017-04-10 23:19:58",
"video_id": 212692070,
"uri": "/videos/212692070"
}
 
 */
function get_vimeo_video($fn_full_url)
{
	$fn_vm = "http://vimeo.com/api/oembed.json?url=".$fn_full_url;

	$curl = curl_init($fn_vm);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$fn_return = curl_exec($curl);
	curl_close($curl);
	$fn_return = json_decode($fn_return, true);

	return $fn_return;
}

/**
 * get_youtube_list function.
 * 
 * @access public
 * @param int $fn_max (default: 5)
 * @return void
 */
function get_youtube_list($fn_youtube_user, $fn_part = 'snippet,id', $fn_orderby = 'date', $fn_max = 50)
{
	global $CONFIG;
	
	$fn_yt = "https://www.googleapis.com/youtube/v3/search?key={$CONFIG['site']['yt_apikey']}&channelId={$fn_youtube_user}&part={$fn_part}&order={$fn_orderby}&maxResults=$fn_max"; 
	
	$fn_data = @file_get_contents($fn_yt);
	$fn_data_decode = json_decode($fn_data);
	
	$fn_result_array = array();
	
	if($fn_data_decode)
	{
		$fn_count = count($fn_data_decode->items);
		
		$fn_i = 0;
		foreach ($fn_data_decode->items as $v )
		{
			if($fn_count == $fn_i) continue;
			if($v->snippet->title == $CONFIG['site']['yt_channel']) continue;
			
			$fn_href = (isset($v->id) && isset($v->id->videoId)) ? 'https://www.youtube.com/embed/'.$v->id->videoId : '';
			$fn_date = date('Y-m-d', strtotime($v->snippet->publishedAt));
			
			$fn_result_array[] = array( 
				'title' => $v->snippet->title,
				'published' => $fn_date, //2012-01-31T16:41:18.000Z
				'publishedDateYt' => $v->snippet->publishedAt, //2012-01-31T16:41:18.000Z
				'description' => htmlize($v->snippet->description),
				'href' => $fn_href,
				'thumb' => $v->{'snippet'}->{'thumbnails'}->medium->url,
			);
			
			$fn_i++;
		}
		
		//ordenaos por fecha de publicacion
		usort($fn_result_array, function($a, $b) 
		{
			return ($a['published'] > $b['published']) ? -1 : 1;
		});
				
		$fn_return = $fn_result_array;
	}else{
		$fn_return = false;
	}
		
	return $fn_return;
}

/**
 * get_vimeo_list function.
 * 
 * @access public
 * @return void
 */
function get_vimeo_list($fn_vimeo_user)
{
	$fn_vm = "http://vimeo.com/api/v2/channel/{$fn_vimeo_user}/videos.json"; 

	$curl = curl_init($fn_vm);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$fn_return = curl_exec($curl);
	curl_close($curl);
	$fn_return = json_decode($fn_return, true);
	
	return $fn_return;
}

?>